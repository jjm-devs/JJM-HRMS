<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollItemLeaveAdjustment;
use App\Models\SalaryStructure;
use App\Services\Hr\HrScopeService;
use App\Services\Hr\OrgUnitStreamService;
use App\Services\Leave\PaidLeaveBankService;
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
        private readonly PaidLeaveBankService $paidBank,
    ) {}

    // ── public entry point ────────────────────────────────────────────────────

    public function generate(
        string $periodFrom,
        string $periodTo,
        ?string $paymentDate = null,
        array $orgUnitIds = [],
        array $departmentStreamIds = [],
        array $staffCategoryIds = [],
        string $batchType = 'regular',
        float $defaultDisbursementPct = 100.00,
    ): PayrollBatch {
        $orgUnitIds = array_values(array_unique(array_map('intval', $orgUnitIds)));
        $staffCategoryIds = array_values(array_unique(array_map('intval', $staffCategoryIds)));
        $from = Carbon::parse($periodFrom)->startOfDay();
        $to   = Carbon::parse($periodTo)->endOfDay();

        $totalWorkingDays = $this->workingDays($from, $to);

        // ── load employees with their active salary structure ─────────────────
        $employeeQuery = Employee::query()
            ->where('service_status', 'active')
            // [NEW] Exclude employees who haven't joined yet as of the period end.
            // Anyone with a joining_date after the batch's period_to has no salary
            // to process for this period at all, so they're filtered out here
            // rather than being fetched and skipped later.
            ->where('joining_date', '<=', $to->toDateString())
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

        if (! empty($orgUnitIds)) {
            $employeeQuery->whereIn('org_unit_id', $orgUnitIds);
        }

        if (! empty($staffCategoryIds)) {
            $employeeQuery->whereIn('staff_category_id', $staffCategoryIds);
        }

        $allowedDepartmentStreamIds = app(OrgUnitStreamService::class)->allowedActiveIdsForAny($orgUnitIds);

        if ($allowedDepartmentStreamIds !== null) {
            $departmentStreamIds = empty($departmentStreamIds)
                ? $allowedDepartmentStreamIds
                : array_values(array_intersect(
                    array_map('intval', $departmentStreamIds),
                    $allowedDepartmentStreamIds,
                ));

            if (empty($departmentStreamIds)) {
                $employeeQuery->whereRaw('1 = 0');
            }
        }

        if (! empty($departmentStreamIds)) {
            $employeeQuery->whereIn('department_stream_id', $departmentStreamIds);
        }

        $employees = $employeeQuery->get();

        // ── load all relevant leave applications in one query ─────────────────
        $leaveMap = $this->buildLeaveMap($employees->pluck('id'), $from, $to);

        // ── paid-leave excess dates (beyond the 2/month bank) per employee ────
        $excessMap = $this->buildPaidLeaveExcessMap($employees->pluck('id'), $from, $to);

        return DB::transaction(function () use (
            $from, $to, $paymentDate, $orgUnitIds, $departmentStreamIds,
            $batchType, $defaultDisbursementPct,
            $employees, $totalWorkingDays, $leaveMap, $excessMap
        ) {
            $batch = PayrollBatch::create([
                'batch_number'             => $this->nextBatchNumber($to, $batchType),
                'batch_type'               => $batchType,
                'default_disbursement_pct' => $defaultDisbursementPct,
                'period_from'              => $from->toDateString(),
                'period_to'                => $to->toDateString(),
                'payment_date'             => $paymentDate,
                'org_unit_id'              => count($orgUnitIds) === 1 ? $orgUnitIds[0] : null,
                'department_stream_id'     => count($departmentStreamIds) === 1 ? $departmentStreamIds[0] : null,
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
                    $excessMap->get($employee->id, []),
                );

                // LWP days = the deductible portion across all leaves
                $lwpDays = (float) $classifications->sum('deductible_days');

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

                // [NEW] Joining-date proration: for an employee whose joining_date
                // falls inside the current period, the days before they joined must
                // not be paid. This reuses the same gross/30-day rate as LWP but is
                // tracked as its own component line so HR can distinguish "didn't
                // work because on leave" from "didn't work because not yet joined".
                $joiningDate = $employee->joining_date ? Carbon::parse($employee->joining_date) : null;
                $prorationDays = $this->joiningProrationDays($from, $to, $joiningDate);

                $prorationDeduction = $this->calculateLwpDeduction(
                    grossEarnings: $itemGross,
                    lwpDays: $prorationDays,
                    totalWorkingDays: $totalWorkingDays,
                );

                if ($prorationDeduction > 0) {
                    $itemDeductions += $prorationDeduction;
                    $componentRows[] = [
                        'salary_component_id' => null,
                        'name'                => 'Joining Proration',
                        'type'                => 'deduction',
                        'amount'              => $prorationDeduction,
                        'calculation_details' => $this->joiningProrationDetails($itemGross, $prorationDays, $joiningDate),
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
                    // [CHANGED] attendance_days now also nets out proration days,
                    // so a mid-period joiner's attendance reflects only the days
                    // they were actually employed for.
                    'attendance_days'      => max($totalWorkingDays - (float) $lwpDays - (float) $prorationDays, 0),
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
                        'deductible_days'      => $row['deductible_days'],
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

    // ── joining-date proration ──────────────────────────────────────────────────
    // [NEW SECTION] Supports paying a mid-period joiner only for the days they
    // were actually employed. Deliberately does NOT handle separations/relieving
    // — that was explicitly out of scope for this change.

    /**
     * [NEW] Working days within the period BEFORE the employee's joining date.
     * Returns 0 if the employee joined on/before the period start (no proration
     * needed) or if there's no joining date on record.
     *
     * Note: the employee query already filters out joining_date > $to, so we
     * don't need to guard against a joining date past the period end here.
     */
    private function joiningProrationDays(Carbon $from, Carbon $to, ?Carbon $joiningDate): float
    {
        if (! $joiningDate || $joiningDate->lte($from)) {
            return 0.0;
        }

        $dayBeforeJoining = $joiningDate->copy()->subDay();

        if ($dayBeforeJoining->lt($from)) {
            return 0.0;
        }

        return (float) $this->workingDays($from, $dayBeforeJoining);
    }

    /**
     * [NEW] Human-readable breakdown for the "Joining Proration" component row.
     */
    private function joiningProrationDetails(float $grossEarnings, float $prorationDays, ?Carbon $joiningDate): string
    {
        return sprintf(
            'Joined %s — Gross ₹%s ÷ %d days × %.1f day(s) before joining',
            $joiningDate?->toDateString() ?? 'N/A',
            number_format($grossEarnings, 2),
            self::SALARY_DAYS_DIVISOR,
            $prorationDays,
        );
    }

    // ── leave classification ──────────────────────────────────────────────────

    /**
     * Classify each leave application:
     *   - Unpaid leave        → salary_deduct (all days).
     *   - Paid Leave (bank)   → leave_bank for the first 2 days/month, salary_deduct
     *                           for days beyond the bank (computed from $excessDates).
     *   - Other paid leave    → exempt (never deducted, e.g. Medical/Maternity).
     *
     * @param  array<int, array<int,string>>  $excessByApp  applicationId => excess Y-m-d dates it owns.
     */
    private function classifyLeaves(Collection $leaves, array $excessByApp): Collection
    {
        return $leaves->sortBy('leave_application_id')->map(function (array $leave) use ($excessByApp): array {
            $days = (float) $leave['leave_days'];

            // Unpaid → full salary deduction
            if (! $leave['leave_type_is_paid']) {
                return array_merge($leave, [
                    'deductible_days'        => $days,
                    'auto_classification'    => 'salary_deduct',
                    'had_sufficient_balance' => false,
                ]);
            }

            // Paid Leave (the monthly bank type)
            if ($this->paidBank->isPaidLeaveType($leave['leave_type_id'])) {
                $ownedExcess = $excessByApp[$leave['leave_application_id']] ?? [];
                $excessInPeriod = (float) count(array_intersect($leave['period_dates'], $ownedExcess));

                return array_merge($leave, [
                    'deductible_days'        => $excessInPeriod,
                    'auto_classification'    => $excessInPeriod > 0 ? 'salary_deduct' : 'leave_bank',
                    'had_sufficient_balance' => $excessInPeriod <= 0,
                ]);
            }

            // Other paid leave types (Medical / Maternity / Paternity) → exempt
            return array_merge($leave, [
                'deductible_days'        => 0.0,
                'auto_classification'    => 'exempt',
                'had_sufficient_balance' => true,
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
                'period_dates'         => $app->days
                    ->pluck('leave_date')
                    ->map(fn ($date) => Carbon::parse($date)->toDateString())
                    ->all(),
            ])->filter(fn ($row) => $row['leave_days'] > 0)->values());
    }

    /**
     * For each employee, the Paid-Leave excess dates (beyond the monthly bank)
     * attributed per leave application. The bank is assessed over the whole
     * calendar month(s) overlapping the period and de-duplicated by date, so a
     * day's status is correct even when banked days sit in a neighbouring period
     * or two applications overlap on the same date.
     *
     * @return Collection<int, array<int, array<int,string>>>  employeeId => [applicationId => excess dates]
     */
    private function buildPaidLeaveExcessMap(Collection $employeeIds, Carbon $from, Carbon $to): Collection
    {
        $paidType = $this->paidBank->paidLeaveType();

        if (! $paidType) {
            return collect();
        }

        $monthsStart = $from->copy()->startOfMonth();
        $monthsEnd = $to->copy()->endOfMonth();

        return LeaveApplication::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('leave_type_id', $paidType->id)
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->where('start_date', '<=', $monthsEnd->toDateString())
            ->where('end_date', '>=', $monthsStart->toDateString())
            ->get()
            ->groupBy('employee_id')
            ->map(fn (Collection $apps) => $this->paidBank->allocateExcess($apps));
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