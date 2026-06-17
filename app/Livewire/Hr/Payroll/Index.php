<?php

namespace App\Livewire\Hr\Payroll;

use App\Models\OrgUnit;
use App\Models\PayrollBatch;
use App\Services\Hr\HrScopeService;
use App\Services\Payroll\PayrollGenerationService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination;

    public string $filterMonth  = '';
    public string $filterStatus = '';

    // ── generate form ─────────────────────────────────────────────────────────
    public string $periodFrom   = '';
    public string $periodTo     = '';
    public string $paymentDate  = '';
    public string $orgUnitId    = '';
    public string $batchType    = 'regular';
    public string $disbursementPct = '100';

    public string $departmentStreamId = '';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
        $this->prefillPeriod();
    }

    // ── modal ─────────────────────────────────────────────────────────────────

    public function openGenerateModal(): void
    {
        abort_unless(auth()->user()?->hasRole('hr'), 403);

        $this->prefillPeriod();
        $this->dispatch('open-modal', name: 'generate-payroll');
    }

    public function closeGenerateModal(): void
    {
        $this->resetErrorBag();
        $this->dispatch('close-modal', name: 'generate-payroll');
    }

    public function generate(): void
    {
        abort_unless(auth()->user()?->hasRole('hr'), 403);

        try {
            $this->validate([
                'periodFrom'         => ['required', 'date'],
                'periodTo'           => ['required', 'date', 'after:periodFrom'],
                'paymentDate'        => ['nullable', 'date'],
                'orgUnitId'          => ['required', 'integer', 'exists:org_units,id'],
                'departmentStreamId' => ['nullable', 'integer', 'exists:department_streams,id'],
                'batchType'          => ['required', 'in:regular,partial'],
                'disbursementPct'    => $this->batchType === 'partial'
                    ? ['required', 'numeric', 'min:1', 'max:99']
                    : [],
            ]);

            $pct = $this->batchType === 'partial'
                ? (float) $this->disbursementPct
                : 100.00;


            $batch = app(PayrollGenerationService::class)->generate(
                periodFrom:             $this->periodFrom,
                periodTo:               $this->periodTo,
                paymentDate:            $this->paymentDate ?: null,
                orgUnitId:              $this->orgUnitId ? (int) $this->orgUnitId : null,
                departmentStreamId:     $this->departmentStreamId ? (int) $this->departmentStreamId : null,
                batchType:              $this->batchType,
                defaultDisbursementPct: $pct,
            );


            $this->closeGenerateModal();
            session()->flash('status', "Batch {$batch->batch_number} generated with {$batch->items()->count()} employees.");
            $this->resetPage();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('open-modal', name: 'generate-payroll');
            throw $e;

        } catch (\Throwable $e) {
            $this->addError('generate', 'Failed to generate payroll: ' . $e->getMessage());
            $this->dispatch('open-modal', name: 'generate-payroll');
        }
    }

    // ── arrear generation ─────────────────────────────────────────────────────

    public function generateArrear(int $batchId): void
    {
        abort_unless(auth()->user()?->hasRole('hr'), 403);

        $partialBatch = PayrollBatch::findOrFail($batchId);

        abort_unless($partialBatch->isPartial(), 403);
        abort_if($partialBatch->hasArrear(), 403);

        try {
            $arrear = app(PayrollGenerationService::class)->generateArrear($partialBatch);
            session()->flash('status', "Arrear batch {$arrear->batch_number} generated.");
            $this->resetPage();
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to generate arrear: '.$e->getMessage());
        }
    }

    // ── render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $scopeService = app(HrScopeService::class);

        $batchQuery = PayrollBatch::query()
            ->with(['generatedBy:id,name', 'orgUnit:id,name'])
            ->withCount('items')
            ->when($this->filterMonth, function ($q) {
                $date = Carbon::parse($this->filterMonth.'-01');
                $q->whereYear('period_to', $date->year)
                    ->whereMonth('period_to', $date->month);
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest();

        $scopedIds = $scopeService->scopedOrgUnitIds();
        if ($scopedIds !== null) {
            $batchQuery->where(fn ($q) => $q
                ->whereIn('org_unit_id', $scopedIds)
                ->orWhereNull('org_unit_id')
            );
        }

        $batches = $batchQuery->paginate(15);

        return view('livewire.hr.payroll.index', [
            'batches'          => $batches,
            'summary'          => $this->summary($scopedIds),
            'orgUnits'         => OrgUnit::query()
                ->where('status', 'active')
                ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
                ->orderBy('name')
                ->pluck('name', 'id'),
            'departmentStreams' => \App\Models\DepartmentStream::where('status', 'active')
                ->orderBy('name')
                ->pluck('name', 'id'),
            'statusOptions'    => $this->statusOptions(),
            'canGeneratePayroll' => auth()->user()?->hasRole('hr') ?? false,
        ]);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function prefillPeriod(): void
    {
        $scopeService = app(\App\Services\Hr\HrScopeService::class);
        $scopedIds    = $scopeService->scopedOrgUnitIds();

        $firstStream = \App\Models\DepartmentStream::where('status', 'active')
            ->orderBy('name')
            ->first();

        $this->periodFrom         = now()->subMonth()->setDay(25)->format('Y-m-d');
        $this->periodTo           = now()->setDay(25)->format('Y-m-d');
        $this->paymentDate        = now()->endOfMonth()->format('Y-m-d');
        $this->orgUnitId          = $scopedIds?->first() ? (string) $scopedIds->first() : '';
        $this->departmentStreamId = $firstStream ? (string) $firstStream->id : '';
        $this->batchType          = 'regular';
        $this->disbursementPct    = '100';
    }

    private function summary(?object $scopedIds): array
    {
        $q = PayrollBatch::query();

        if ($scopedIds !== null) {
            $q->where(fn ($q) => $q
                ->whereIn('org_unit_id', $scopedIds)
                ->orWhereNull('org_unit_id')
            );
        }

        $all = $q->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        return [
            'total'     => $all->sum(),
            'draft'     => $all->get('draft', 0),
            'returned'  => $all->get('returned', 0),
            'pending'   => $all->get('pending', 0),
            'approved'  => $all->get('approved', 0),
            'locked'    => $all->get('locked', 0),
            'disbursed' => $all->get('disbursed', 0),
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft'     => 'Draft',
            'returned'  => 'Returned to HR',
            'pending'   => 'Pending Approval',
            'approved'  => 'Approved',
            'locked'    => 'Locked',
            'disbursed' => 'Disbursed',
        ];
    }
}
