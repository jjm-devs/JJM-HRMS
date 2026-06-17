<?php

namespace App\Livewire\Employee\Payslips;

use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function downloadPayslip(int $payslipId)
    {
        $payslip = $this->payslipForEmployee($payslipId);
        $document = $payslip->document;

        abort_if(! $document, 404);
        abort_unless(Storage::disk($document->disk)->exists($document->file_path), 404);

        $document->accessLogs()->create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $payslip->increment('download_count');

        return Storage::disk($document->disk)->download($document->file_path, $document->file_name);
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

    private function payslipForEmployee(int $payslipId): Payslip
    {
        return Payslip::query()
            ->with(['document', 'payrollItem.payrollBatch'])
            ->whereKey($payslipId)
            ->where('status', 'issued')
            ->whereHas('payrollItem', fn ($query) => $query->where('employee_id', $this->employee->id))
            ->firstOrFail();
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
