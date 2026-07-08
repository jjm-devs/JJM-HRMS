<?php

namespace App\Livewire\Hr\Reports;

use App\Models\DepartmentStream;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\OrgUnit;
use App\Models\PayrollItem;
use App\Models\TransferRequest;
use App\Services\Hr\HrScopeService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $filterOrgUnit = '';

    public string $filterDepartmentStream = '';

    private HrScopeService $scope;

    public function mount(): void
    {
        $this->dateFrom = now()->subMonthsNoOverflow(3)->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /** Employee roster (respects the office/stream filters). */
    public function downloadRoster(): StreamedResponse
    {
        $this->scope = app(HrScopeService::class);

        $employees = $this->scope
            ->applyToEmployeeQuery(
                Employee::query()
                    ->with(['designation:id,name', 'orgUnit:id,name', 'departmentStream:id,name', 'staffCategory:id,name', 'employmentType:id,name'])
                    ->when($this->filterOrgUnit !== '', fn ($q) => $q->where('org_unit_id', $this->filterOrgUnit))
                    ->when($this->filterDepartmentStream !== '', fn ($q) => $q->where('department_stream_id', $this->filterDepartmentStream))
                    ->orderBy('full_name')
            )
            ->get();

        $rows = $employees->map(fn (Employee $e) => [
            $e->employee_code,
            $e->full_name,
            $e->designation?->name,
            $e->orgUnit?->name,
            $e->departmentStream?->name,
            $e->staffCategory?->name ?? 'None',
            $e->employmentType?->name,
            $e->service_status,
            $e->bank_name,
            $e->bank_ifsc_code,
        ]);

        return $this->csv('employee-roster', [
            'Employee Code', 'Name', 'Designation', 'Office', 'Stream', 'Staff Category', 'Employment', 'Status', 'Bank', 'IFSC',
        ], $rows);
    }

    /** Payroll lines within the selected date range (by batch period). */
    public function downloadPayroll(): StreamedResponse
    {
        $this->scope = app(HrScopeService::class);

        $items = $this->scope
            ->applyToEmployeeRelatedQuery(
                PayrollItem::query()
                    ->with(['employee:id,full_name,employee_code,org_unit_id', 'employee.orgUnit:id,name', 'payrollBatch:id,batch_number,period_from,period_to'])
                    ->whereHas('payrollBatch', fn ($q) => $q
                        ->whereDate('period_from', '>=', $this->dateFrom)
                        ->whereDate('period_from', '<=', $this->dateTo))
            )
            ->get();

        $rows = $items->map(fn (PayrollItem $i) => [
            $i->payrollBatch?->batch_number,
            $i->payrollBatch?->period_from?->format('d M Y').' – '.$i->payrollBatch?->period_to?->format('d M Y'),
            $i->employee?->employee_code,
            $i->employee?->full_name,
            $i->employee?->orgUnit?->name,
            number_format((float) $i->gross_salary, 2, '.', ''),
            number_format((float) $i->total_deductions, 2, '.', ''),
            number_format((float) $i->net_salary, 2, '.', ''),
            $i->status,
        ]);

        return $this->csv('payroll-summary', [
            'Batch', 'Period', 'Employee Code', 'Name', 'Office', 'Gross', 'Deductions', 'Net', 'Status',
        ], $rows);
    }

    /** Leave applications within the selected date range (by start date). */
    public function downloadLeave(): StreamedResponse
    {
        $this->scope = app(HrScopeService::class);

        $leaves = $this->scope
            ->applyToEmployeeRelatedQuery(
                LeaveApplication::query()
                    ->with(['employee:id,full_name,employee_code', 'leaveType:id,name'])
                    ->whereDate('start_date', '>=', $this->dateFrom)
                    ->whereDate('start_date', '<=', $this->dateTo)
                    ->latest('start_date')
            )
            ->get();

        $rows = $leaves->map(fn (LeaveApplication $l) => [
            $l->employee?->employee_code,
            $l->employee?->full_name,
            $l->leaveType?->name,
            $l->start_date?->format('d M Y'),
            $l->end_date?->format('d M Y'),
            $l->total_days,
            $l->status,
        ]);

        return $this->csv('leave-summary', [
            'Employee Code', 'Name', 'Leave Type', 'From', 'To', 'Days', 'Status',
        ], $rows);
    }

    /** Transfers recorded within the selected date range. */
    public function downloadTransfers(): StreamedResponse
    {
        $this->scope = app(HrScopeService::class);

        $transfers = $this->scope
            ->applyToEmployeeRelatedQuery(
                TransferRequest::query()
                    ->with(['employee:id,full_name,employee_code', 'fromOrgUnit:id,name', 'toOrgUnit:id,name'])
                    ->whereDate('created_at', '>=', $this->dateFrom)
                    ->whereDate('created_at', '<=', $this->dateTo)
                    ->latest('id')
            )
            ->get();

        $types = [
            'administrative' => 'Administrative',
            'request' => 'On Request',
            'promotion' => 'Promotion',
            'deputation' => 'Deputation',
        ];

        $rows = $transfers->map(fn (TransferRequest $t) => [
            $t->employee?->employee_code,
            $t->employee?->full_name,
            $t->fromOrgUnit?->name,
            $t->toOrgUnit?->name,
            $types[$t->transfer_type] ?? $t->transfer_type,
            $t->effective_date?->format('d M Y'),
            $t->status,
        ]);

        return $this->csv('transfer-register', [
            'Employee Code', 'Name', 'From Office', 'To Office', 'Type', 'Effective Date', 'Status',
        ], $rows);
    }

    public function render()
    {
        $this->scope = app(HrScopeService::class);

        $orgUnits = $this->scope->isUnrestricted()
            ? OrgUnit::query()->orderBy('type')->orderBy('name')->get(['id', 'name'])
            : OrgUnit::query()->whereIn('id', $this->scope->scopedOrgUnitIds() ?? collect())->orderBy('name')->get(['id', 'name']);

        return view('livewire.hr.reports.index', [
            'orgUnits' => $orgUnits,
            'departmentStreams' => DepartmentStream::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Stream an array of rows as a downloadable CSV.
     *
     * @param  array<int, string>  $header
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private function csv(string $name, array $header, iterable $rows): StreamedResponse
    {
        $filename = $name.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
