<?php

namespace App\Livewire\Hr\Payroll;

use App\Models\OrgUnit;
use App\Models\PayrollBatch;
use App\Services\Hr\HrScopeService;
use App\Services\Hr\OrgUnitStreamService;
use App\Services\Payroll\PayrollGenerationService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $filterMonth  = '';
    public string $filterStatus = '';

    // ── generate form ─────────────────────────────────────────────────────────
    public string $periodFrom   = '';
    public string $periodTo     = '';
    public string $paymentDate  = '';
    public string $orgUnitId    = '';       // single office (non-HO HR)
    public array  $orgUnitIds   = [];        // multiple offices (Head Office HR)
    public string $batchType    = 'regular';
    public string $disbursementPct = '100';

    public array $departmentStreamIds = [];

    public function mount(): void
    {
        abort_unless(app(HrScopeService::class)->canAccessPayrollModule(), 403);

        $this->filterMonth = now()->format('Y-m');
        $this->prefillPeriod();
    }

    // ── modal ─────────────────────────────────────────────────────────────────

    public function openGenerateModal(): void
    {
        abort_unless(app(HrScopeService::class)->isHeadOfficeHr(), 403);

        $this->prefillPeriod();
        $this->dispatch('open-modal', name: 'generate-payroll');
    }

    public function closeGenerateModal(): void
    {
        $this->resetErrorBag();
        $this->dispatch('close-modal', name: 'generate-payroll');
    }

    public function updatedOrgUnitId(): void
    {
        $this->syncStreamsToSelectedOffices();
    }

    public function updatedOrgUnitIds(): void
    {
        $this->syncStreamsToSelectedOffices();
    }

    public function selectAllOffices(): void
    {
        $scopedIds = app(HrScopeService::class)->scopedOrgUnitIds();

        $this->orgUnitIds = OrgUnit::query()
            ->where('status', 'active')
            ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->syncStreamsToSelectedOffices();
    }

    public function clearOffices(): void
    {
        $this->orgUnitIds = [];
        $this->syncStreamsToSelectedOffices();
    }

    private function syncStreamsToSelectedOffices(): void
    {
        $allowed = app(OrgUnitStreamService::class)->allowedActiveIdsForAny($this->selectedOfficeIds());

        if ($allowed === null) {
            return; // no restriction — keep current stream selection
        }

        $this->departmentStreamIds = array_values(array_filter(
            $this->departmentStreamIds,
            fn ($id) => in_array((int) $id, $allowed, true),
        ));
    }

    private function isHeadOfficeHr(): bool
    {
        return app(HrScopeService::class)->isHeadOfficeHr();
    }

    /**
     * The effective set of office ids for the current user (empty = all in scope).
     *
     * @return array<int, int>
     */
    private function selectedOfficeIds(): array
    {
        if ($this->isHeadOfficeHr()) {
            return array_values(array_unique(array_map('intval', $this->orgUnitIds)));
        }

        return $this->orgUnitId !== '' ? [(int) $this->orgUnitId] : [];
    }

    public function generate(): void
    {
        abort_unless(app(HrScopeService::class)->isHeadOfficeHr(), 403);

        try {
            $rules = [
                'periodFrom'         => ['required', 'date'],
                'periodTo'           => ['required', 'date', 'after:periodFrom'],
                'paymentDate'        => ['nullable', 'date'],
                'departmentStreamIds'   => ['nullable', 'array'],
                'departmentStreamIds.*' => ['integer', $this->departmentStreamRule()],
                'batchType'          => ['required', 'in:regular,partial'],
                'disbursementPct'    => $this->batchType === 'partial'
                    ? ['required', 'numeric', 'min:1', 'max:99']
                    : [],
            ];

            if ($this->isHeadOfficeHr()) {
                // Head Office HR: pick one or more offices in scope (at least one).
                $rules['orgUnitIds'] = ['required', 'array', 'min:1'];
                $rules['orgUnitIds.*'] = ['integer', $this->orgUnitRule()];
            } else {
                // Other HR: a single office, still within scope.
                $rules['orgUnitId'] = ['required', 'integer', $this->orgUnitRule()];
            }

            $this->validate($rules, [
                'orgUnitIds.required' => 'Select at least one office.',
                'orgUnitIds.min' => 'Select at least one office.',
            ]);

            $offices = $this->selectedOfficeIds();

            $pct = $this->batchType === 'partial'
                ? (float) $this->disbursementPct
                : 100.00;

            $batch = app(PayrollGenerationService::class)->generate(
                periodFrom:             $this->periodFrom,
                periodTo:               $this->periodTo,
                paymentDate:            $this->paymentDate ?: null,
                orgUnitIds:             $offices,
                departmentStreamIds:    array_map('intval', $this->departmentStreamIds),
                batchType:              $this->batchType,
                defaultDisbursementPct: $pct,
            );

            $this->rememberSelection($offices);

            $this->closeGenerateModal();
            session()->flash('status', $this->generatedMessage($batch));
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
        abort_unless(app(HrScopeService::class)->isHeadOfficeHr(), 403);

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
            'orgUnits'         => $this->orgUnitOptions($scopedIds),
            'departmentStreams' => app(OrgUnitStreamService::class)->activeOptionsForAny($this->selectedOfficeIds()),
            'statusOptions'    => $this->statusOptions(),
            'canGeneratePayroll' => app(HrScopeService::class)->isHeadOfficeHr(),
            'isHeadOfficeHr'   => $this->isHeadOfficeHr(),
        ]);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /**
     * Scoped active offices, with the currently-selected ones pulled to the top
     * (alphabetical within each group).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function orgUnitOptions(?object $scopedIds): \Illuminate\Support\Collection
    {
        $offices = OrgUnit::query()
            ->where('status', 'active')
            ->when($scopedIds !== null, fn ($q) => $q->whereIn('id', $scopedIds))
            ->orderBy('name')
            ->pluck('name', 'id');

        $selectedIds = $this->selectedOfficeIds();

        $selected = $offices->filter(fn ($name, $id) => in_array((int) $id, $selectedIds, true));
        $unselected = $offices->reject(fn ($name, $id) => in_array((int) $id, $selectedIds, true));

        return $selected->union($unselected);
    }

    private function prefillPeriod(): void
    {
        $scopeService = app(\App\Services\Hr\HrScopeService::class);
        $scopedIds    = $scopeService->scopedOrgUnitIds();

        $this->periodFrom         = now()->subMonth()->setDay(25)->format('Y-m-d');
        $this->periodTo           = now()->setDay(25)->format('Y-m-d');
        $this->paymentDate        = now()->endOfMonth()->format('Y-m-d');
        $this->batchType          = 'regular';
        $this->disbursementPct    = '100';

        // Pre-fill the office/stream selection from the user's last generation.
        $defaults      = auth()->user()?->payroll_generation_defaults ?? [];
        $savedOffices  = array_map('intval', $defaults['org_unit_ids'] ?? []);
        $savedStreams  = array_map('intval', $defaults['department_stream_ids'] ?? []);

        if ($scopedIds !== null) {
            $savedOffices = array_values(array_intersect($savedOffices, $scopedIds->all()));
        }

        if ($this->isHeadOfficeHr()) {
            $this->orgUnitIds = array_map('strval', $savedOffices);
            $this->orgUnitId  = '';
        } else {
            $this->orgUnitIds = [];
            $this->orgUnitId  = $savedOffices
                ? (string) $savedOffices[0]
                : ($scopedIds?->first() ? (string) $scopedIds->first() : '');
        }

        // Keep only streams still valid for the pre-filled offices.
        $allowedStreamIds = array_keys(
            app(OrgUnitStreamService::class)->activeOptionsForAny($this->selectedOfficeIds())
        );
        $this->departmentStreamIds = array_values(array_map(
            'strval',
            array_intersect($savedStreams, $allowedStreamIds)
        ));
    }

    private function departmentStreamRule()
    {
        $allowedIds = app(OrgUnitStreamService::class)->allowedActiveIdsForAny($this->selectedOfficeIds());

        return $allowedIds === null
            ? Rule::exists('department_streams', 'id')->where('status', 'active')
            : Rule::in($allowedIds);
    }

    private function orgUnitRule()
    {
        $scopedIds = app(HrScopeService::class)->scopedOrgUnitIds();

        return $scopedIds === null
            ? Rule::exists('org_units', 'id')
            : Rule::in($scopedIds->all());
    }

    private function rememberSelection(array $offices): void
    {
        auth()->user()?->update([
            'payroll_generation_defaults' => [
                'org_unit_ids' => array_values($offices),
                'department_stream_ids' => array_values(array_map('intval', $this->departmentStreamIds)),
            ],
        ]);
    }

    private function generatedMessage(PayrollBatch $batch): string
    {
        $message = "Batch {$batch->batch_number} generated with {$batch->items()->count()} employees.";

        $overlap = $this->overlappingEmployeeCount($batch);
        if ($overlap > 0) {
            $message .= " Note: {$overlap} of them are already in another batch for an overlapping period.";
        }

        return $message;
    }

    private function overlappingEmployeeCount(PayrollBatch $batch): int
    {
        $employeeIds = $batch->items()->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            return 0;
        }

        return \App\Models\PayrollItem::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('payroll_batch_id', '!=', $batch->id)
            ->whereHas('payrollBatch', fn ($q) => $q
                ->whereDate('period_from', '<=', $batch->period_to)
                ->whereDate('period_to', '>=', $batch->period_from))
            ->distinct()
            ->count('employee_id');
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
