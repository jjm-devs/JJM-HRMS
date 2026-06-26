<?php

namespace App\Livewire\Employee\Payslips;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public Employee $employee;

    public string $filterYear = '';

    public function mount(): void
    {
        $this->employee = Auth::user()->employee()->firstOrFail();
        $this->filterYear = now()->format('Y');
    }

    public function render()
    {
        $payslips = Payslip::query()
            ->with(['document', 'payrollItem.payrollBatch'])
            ->where('status', 'issued')
            ->whereHas('payrollItem', fn ($query) => $query->where('employee_id', $this->employee->id))
            ->when($this->filterYear !== '', function ($query): void {
                $query->whereHas('payrollItem.payrollBatch', fn ($batchQuery) => $batchQuery
                    ->whereYear('period_to', $this->filterYear)
                );
            })
            ->latest('generated_at')
            ->latest('id')
            ->get();

        return view('livewire.employee.payslips.index', [
            'payslips' => $payslips,
            'years' => $this->availableYears(),
            'summary' => [
                'issued' => $payslips->count(),
                'downloads' => $payslips->sum('download_count'),
                'latest' => $payslips->first(),
            ],
        ]);
    }

    private function availableYears(): array
    {
        return Payslip::query()
            ->where('status', 'issued')
            ->whereHas('payrollItem', fn ($query) => $query->where('employee_id', $this->employee->id))
            ->whereHas('payrollItem.payrollBatch')
            ->with('payrollItem.payrollBatch:id,period_to')
            ->get()
            ->pluck('payrollItem.payrollBatch.period_to')
            ->filter()
            ->map(fn ($date) => $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->all();
    }
}
