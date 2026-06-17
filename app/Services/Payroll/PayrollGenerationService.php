<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveBalance;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemLeaveAdjustment;
use App\Models\SalaryStructure;
use App\Services\Hr\HrScopeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollGenerationService
{
    /**
     * The divisor used for per-day salary calculation.
     *
     * Government standard: fixed 30 days regardless of month length or pay period.
     * To switch to actual working days in the period, replace the value with
     * $totalWorkingDays passed into calculateLwpDeduction() — the method signature
     * already accepts it as a parameter for exactly this reason.
     */
    private const SALARY_DAYS_DIVISOR = 30;

    public function __construct(
        private readonly HrScopeService $hrScope,
    ) {}

    // ── public entry point ────────────────────────────────────────────────────

    public function generate(
        string $periodFrom,
        string $periodTo,
        ?string $paymentDate = null,
        ?int $orgUnitId = null,
        ?int $departmentStreamId = null,
        string $batchType = 'regular',
        float $defaultDisbursementPct = 100.00,
    ): PayrollBatch {
        $from = Carbon::parse($periodFrom)->startOfDay();
        $to   = Carbon::parse($periodTo)->endOfDay();

        $totalWorkingDays = $this->workingDays($from, $to);

        // ── load employees with their active salary structure ─────────────────
        $employeeQuery = Employee::query()
            ->where('service_status', 'active')
            ->with([
                'salaryStructures' => fn ($q) => $q
                    ->where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $to))
                    ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from))
                    ->with(['employeeSalaryComponents.salaryComponent'])
                    ->latest('effective_from')
                    ->limit(1),
            ]);

        $this->hrScope->applyToEmployeeQuery($employeeQuery);

        if ($orgUnitId !== null) {
            $employeeQuery->where('org_unit_id', $orgUnitId);
        }

        if ($departmentStreamId !== null) {
            $employeeQuery->where('department_stream_id', $departmentStreamId);
        }

        $employees = $employeeQuery->get();

        // ── load all relevant leave applications in one query ─────────────────
        $leaveMap = $this->buildLeaveMap($employees->pluck('id'), $from, $to);

        // ── load leave balances for all employees + leave types in one query ──
        $balanceMap = $this->buildBalanceMap($employees->pluck('id'), $from);

        return DB::transaction(function () use (
            $from, $to, $paymentDate, $orgUnitId, $departmentStreamId,
            $batchType, $defaultDisbursementPct,
            $employees, $totalWorkingDays, $leaveMap, $balanceMap
        ) {
            $batch = PayrollBatch::create([
                'batch_number'             => $this->nextBatchNumber($to, $batchType),
                'batch_type'               => $batchType,
                'default_disbursement_pct' => $defaultDisbursementPct,
                'period_from'              => $from->toDateString(),
                'period_to'                => $to->toDateString(),
                'payment_date'             => $paymentDate,
                'org_unit_id'              => $orgUnitId,
                'department_stream_id'     => $departmentStreamId,
                'generated_by'             => Auth::id(),
                'status'                   => 'draft',
            ]);

            $grossTotal      = 0;
            $deductionTotal  = 0;
            $netTotal        = 0;
            $disbursedTotal  = 0;

            foreach ($employees as $employee) {
                /** @var SalaryStructure|null $structure */
                $structure = $employee->salaryStructures->first();

                if ($structure === null) {
                    continue; // no active salary structure — skip
                }

                // leave applications for this employee in this period
                $employeeLeaves = $leaveMap->get($employee->id, collect());

                // classify each leave application as salary_deduct / leave_bank / exempt
                $classifications = $this->classifyLeaves(
                    $employeeLeaves,
                    $balanceMap->get($employee->id, collect()),
                );

                // sum up LWP days from salary_deduct leaves only
                $lwpDays = $classifications
                    ->where('auto_classification', 'salary_deduct')
                    ->sum('leave_days');

                // calculate salary components
                [$itemGross, $itemDeductions, $componentRows] = $this->calculateItem(
                    $structure,
                    $structure->employeeSalaryComponents,
                );

                // LWP deduction is against gross earnings using the configured divisor
                $lwpDeduction = $this->calculateLwpDeduction(
                    grossEarnings: $itemGross,
                    lwpDays: $lwpDays,
                    totalWorkingDays: $totalWorkingDays,
                );

                if ($lwpDeduction > 0) {
                    $itemDeductions += $lwpDeduction;
                    $componentRows[] = [
                        'salary_component_id' => null,
                        'name'                => 'Leave Without Pay',
                        'type'                => 'deduction',
                        'amount'              => $lwpDeduction,
                        'calculation_details' => $this->lwpDetails($itemGross, $lwpDays),
                        'is_manually_adjusted' => false,
                    ];
                }

                $netSalary = max($itemGross - $itemDeductions, 0);

                // ── disbursement amounts ───────────────────────────────────────
                $disbursedAmount  = round($netSalary * $defaultDisbursementPct / 100, 2);
                $outstandingAmount = round($netSalary - $disbursedAmount, 2);

                $item = $batch->items()->create([
                    'employee_id'          => $employee->id,
                    'basic_salary'         => (float) $structure->basic_salary,
                    'gross_salary'         => $itemGross,
                    'total_deductions'     => round($itemDeductions, 2),
                    'net_salary'           => round($netSalary, 2),
                    'disbursement_pct'     => $defaultDisbursementPct,
                    'disbursed_amount'     => $disbursedAmount,
                    'outstanding_amount'   => $outstandingAmount,
                    'attendance_days'      => max($totalWorkingDays - (float) $lwpDays, 0),
                    'leave_without_pay_days' => $lwpDays,
                    'lwp_deduction'        => $lwpDeduction,
                    'status'               => 'draft',
                ]);

                // persist salary components
                foreach ($componentRows as $row) {
                    $item->components()->create($row);
                }

                // persist leave adjustments for HR review
                foreach ($classifications as $row) {
                    PayrollItemLeaveAdjustment::create([
                        'payroll_item_id'      => $item->id,
                        'leave_application_id' => $row['leave_application_id'],
                        'leave_days'           => $row['leave_days'],
                        'auto_classification'  => $row['auto_classification'],
                        'hr_classification'    => null,
                        'leave_type_name'      => $row['leave_type_name'],
                        'leave_type_is_paid'   => $row['leave_type_is_paid'],
                        'had_sufficient_balance' => $row['had_sufficient_balance'],
                    ]);
                }

                $grossTotal     += $itemGross;
                $deductionTotal += $itemDeductions;
                $netTotal       += $netSalary;
                $disbursedTotal += $disbursedAmount;
            }

            $batch->update([
                'gross_total'      => round($grossTotal, 2),
                'deduction_total'  => round($deductionTotal, 2),
                'net_total'        => round($netTotal, 2),
                'disbursed_total'  => round($disbursedTotal, 2),
            ]);

            return $batch;
        });
    }

    // ── arrear batch generation ───────────────────────────────────────────────

    /**
     * Generate an arrear batch from a partial batch.
     * Each item's disbursed_amount = original net_salary - partial disbursed_amount.
     * No leave review or adjustments — locked to the outstanding amounts only.
     */
    public function generateArrear(
        PayrollBatch $partialBatch,
        ?string $paymentDate = null,
    ): PayrollBatch {
        abort_unless($partialBatch->isPartial(), 422, 'Arrear can only be generated from a partial batch.');
        abort_if($partialBatch->hasArrear(), 422, 'An arrear batch already exists for this partial batch.');

        return DB::transaction(function () use ($partialBatch, $paymentDate) {
            $arrearBatch = PayrollBatch::create([
                'batch_number'             => $this->nextBatchNumber(Carbon::parse($partialBatch->period_to), 'arrear'),
                'batch_type'               => 'arrear',
                'parent_batch_id'          => $partialBatch->id,
                'default_disbursement_pct' => 100.00,
                'period_from'              => $partialBatch->period_from,
                'period_to'                => $partialBatch->period_to,
                'payment_date'             => $paymentDate,
                'org_unit_id'              => $partialBatch->org_unit_id,
                'generated_by'             => Auth::id(),
                'status'                   => 'draft',
            ]);

            $netTotal      = 0;
            $disbursedTotal = 0;

            foreach ($partialBatch->items as $partialItem) {
                $outstanding = (float) $partialItem->outstanding_amount;

                if ($outstanding <= 0) {
                    continue; // nothing owed — skip
                }

                $arrearBatch->items()->create([
                    'employee_id'          => $partialItem->employee_id,
                    'basic_salary'         => $partialItem->basic_salary,
                    'gross_salary'         => $outstanding,
                    'total_deductions'     => 0,
                    'net_salary'           => $outstanding,
                    'disbursement_pct'     => 100.00,
                    'disbursed_amount'     => $outstanding,
                    'outstanding_amount'   => 0,
                    'attendance_days'      => $partialItem->attendance_days,
                    'leave_without_pay_days' => 0,
                    'lwp_deduction'        => 0,
                    'status'               => 'draft',
                ]);

                $netTotal       += $outstanding;
                $disbursedTotal += $outstanding;
            }

            $arrearBatch->update([
                'gross_total'     => round($netTotal, 2),
                'deduction_total' => 0,
                'net_total'       => round($netTotal, 2),
                'disbursed_total' => round($disbursedTotal, 2),
            ]);

            return $arrearBatch;
        });
    }

    // ── LWP deduction ─────────────────────────────────────────────────────────

    private function calculateLwpDeduction(
        float $grossEarnings,
        float $lwpDays,
        int $totalWorkingDays,
    ): float {
        if ($lwpDays <= 0 || $grossEarnings <= 0) {
            return 0.0;
        }

        $divisor = self::SALARY_DAYS_DIVISOR;

        return round($grossEarnings / $divisor * $lwpDays, 2);
    }

    private function lwpDetails(float $grossEarnings, float $lwpDays): string
    {
        return sprintf(
            'Gross ₹%s ÷ %d days × %.1f LWP days',
            number_format($grossEarnings, 2),
            self::SALARY_DAYS_DIVISOR,
            $lwpDays,
        );
    }

    // ── leave classification ──────────────────────────────────────────────────

    private function classifyLeaves(
        Collection $leaves,
        Collection $balances,
    ): Collection {
        $remainingBalances = $balances
            ->map(fn (LeaveBalance $balance): float => (float) $balance->closing_balance)
            ->all();

        return $leaves->sortBy('leave_application_id')->map(function (array $leave) use (&$remainingBalances): array {
            $isPaid = $leave['leave_type_is_paid'];

            if (! $isPaid) {
                return array_merge($leave, [
                    'auto_classification'    => 'salary_deduct',
                    'had_sufficient_balance' => false,
                ]);
            }

            $availableBalance     = $remainingBalances[$leave['leave_type_id']] ?? 0.0;
            $hasSufficientBalance = $availableBalance >= $leave['leave_days'];

            if ($hasSufficientBalance) {
                $remainingBalances[$leave['leave_type_id']] = $availableBalance - (float) $leave['leave_days'];
            }

            return array_merge($leave, [
                'auto_classification'    => $hasSufficientBalance ? 'leave_bank' : 'salary_deduct',
                'had_sufficient_balance' => $hasSufficientBalance,
            ]);
        });
    }

    // ── salary component calculation ──────────────────────────────────────────

    private function calculateItem(
        SalaryStructure $structure,
        Collection $components,
    ): array {
        $basicSalary = (float) $structure->basic_salary;
        $gradePay    = (float) $structure->grade_pay;

        $gross      = 0.0;
        $deductions = 0.0;
        $rows       = [];

        $hasBasicComponent = $components->contains(
            fn (EmployeeSalaryComponent $component): bool => $this->isBasicSalaryComponent($component->salaryComponent)
                && $component->status === 'active'
        );

        if (! $hasBasicComponent && $basicSalary > 0) {
            $gross  += $basicSalary;
            $rows[] = [
                'salary_component_id'  => null,
                'name'                 => 'Basic Salary',
                'type'                 => 'earning',
                'amount'               => $basicSalary,
                'calculation_details'  => 'Fallback from salary structure',
                'is_manually_adjusted' => false,
            ];
        }

        if ($gradePay > 0) {
            $gross  += $gradePay;
            $rows[] = [
                'salary_component_id'  => null,
                'name'                 => 'Grade Pay',
                'type'                 => 'earning',
                'amount'               => $gradePay,
                'calculation_details'  => 'Grade pay from salary structure',
                'is_manually_adjusted' => false,
            ];
        }

        foreach ($components as $ec) {
            $component = $ec->salaryComponent;

            if (! $component || $ec->status !== 'active') {
                continue;
            }

            $amount = (float) $ec->amount;

            if ($amount <= 0) {
                continue;
            }

            $componentType = $component->type ?? ($component->is_deduction ? 'deduction' : 'earning');
            $isDeduction   = (bool) $component->is_deduction || $componentType === 'deduction';

            if ($isDeduction) {
                $deductions += $amount;
            } elseif ($componentType === 'earning') {
                $gross += $amount;
            }

            $rows[] = [
                'salary_component_id'  => $component->id,
                'name'                 => $component->name,
                'type'                 => $isDeduction ? 'deduction' : $componentType,
                'amount'               => round($amount, 2),
                'calculation_details'  => $this->buildDetails($ec, $amount, $basicSalary),
                'is_manually_adjusted' => false,
            ];
        }

        return [round($gross, 2), round($deductions, 2), $rows];
    }

    private function buildDetails(
        EmployeeSalaryComponent $ec,
        float $amount,
        float $basicSalary,
    ): string {
        $calcType = $ec->calculation_type ?? $ec->salaryComponent?->calculation_type;

        return match ($calcType) {
            'percentage' => sprintf(
                '%.2f%% on %s',
                $ec->percentage_rate,
                $this->calculationBaseLabel($ec->calculation_base),
            ),
            'formula' => 'Formula note: '.($ec->formula ?? $ec->salaryComponent?->formula ?? ''),
            default    => sprintf('Fixed amount ₹%s', number_format($amount, 2)),
        };
    }

    private function calculationBaseLabel(?string $base): string
    {
        return match ($base) {
            'basic_salary'          => 'Basic Salary',
            'basic_plus_grade_pay'  => 'Basic Salary + Grade Pay',
            'gross_earnings'        => 'Gross Earnings',
            default                 => 'selected base',
        };
    }

    private function isBasicSalaryComponent(?object $component): bool
    {
        if (! $component) {
            return false;
        }

        return strtoupper((string) $component->code) === 'BASIC'
            || str_contains(strtolower((string) $component->name), 'basic');
    }

    // ── data loading ──────────────────────────────────────────────────────────

    private function buildLeaveMap(
        Collection $employeeIds,
        Carbon $from,
        Carbon $to,
    ): Collection {
        $applications = LeaveApplication::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->where('start_date', '<=', $to->toDateString())
            ->where('end_date', '>=', $from->toDateString())
            ->with([
                'leaveType:id,name,is_paid',
                'days' => fn ($q) => $q
                    ->whereBetween('leave_date', [$from->toDateString(), $to->toDateString()])
                    ->where('status', LeaveApplication::STATUS_APPROVED),
            ])
            ->get();

        return $applications
            ->groupBy('employee_id')
            ->map(fn (Collection $apps) => $apps->map(fn ($app) => [
                'leave_application_id' => $app->id,
                'leave_type_id'        => $app->leave_type_id,
                'leave_type_name'      => $app->leaveType->name,
                'leave_type_is_paid'   => (bool) $app->leaveType->is_paid,
                'leave_days'           => $app->days->sum('duration'),
            ])->filter(fn ($row) => $row['leave_days'] > 0)->values());
    }

    private function buildBalanceMap(Collection $employeeIds, Carbon $from): Collection
    {
        return LeaveBalance::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('year', $from->year)
            ->get()
            ->groupBy('employee_id')
            ->map(fn (Collection $balances) => $balances->keyBy('leave_type_id'));
    }

    // ── working days ──────────────────────────────────────────────────────────

    private function workingDays(Carbon $from, Carbon $to): int
    {
        $count   = 0;
        $current = $from->copy()->startOfDay();

        while ($current->lte($to)) {
            $dow = $current->dayOfWeek;

            if ($dow === Carbon::SUNDAY) {
                $current->addDay();
                continue;
            }

            if ($dow === Carbon::SATURDAY && $this->isNonWorkingSaturday($current)) {
                $current->addDay();
                continue;
            }

            $count++;
            $current->addDay();
        }

        return $count;
    }

    private function isNonWorkingSaturday(Carbon $date): bool
    {
        $satCount = 0;
        $cursor   = $date->copy()->startOfMonth();

        while ($cursor->lte($date)) {
            if ($cursor->dayOfWeek === Carbon::SATURDAY) {
                $satCount++;
            }
            $cursor->addDay();
        }

        return in_array($satCount, [2, 4]);
    }

    // ── batch number ──────────────────────────────────────────────────────────

    private function nextBatchNumber(Carbon $periodTo, string $batchType = 'regular'): string
    {
        $prefix = match ($batchType) {
            'partial' => 'PAR',
            'arrear'  => 'ARR',
            default   => 'PAY',
        };

        $count = PayrollBatch::query()
            ->where('batch_type', $batchType)
            ->whereYear('period_to', $periodTo->year)
            ->whereMonth('period_to', $periodTo->month)
            ->count();

        return sprintf('%s-%d-%02d-%03d', $prefix, $periodTo->year, $periodTo->month, $count + 1);
    }
}