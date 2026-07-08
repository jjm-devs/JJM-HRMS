<?php

namespace App\Livewire\Hr\Transfers;

use App\Models\Employee;
use App\Models\OrgUnit;
use App\Models\PostingHistory;
use App\Models\TransferRequest;
use App\Services\Hr\HrScopeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public array $form = [
        'employee_id' => '',
        'to_org_unit_id' => '',
        'transfer_type' => 'administrative',
        'effective_date' => '',
        'reason' => '',
        'remarks' => '',
    ];

    /** @var array<string, string> */
    public array $typeOptions = [
        'administrative' => 'Administrative',
        'request' => 'On Request',
        'promotion' => 'Promotion',
        'deputation' => 'Deputation',
    ];

    private HrScopeService $scope;

    public function mount(): void
    {
        $this->form['effective_date'] = now()->format('Y-m-d');
    }

    public function updated($property): void
    {
        if ($property === 'search' || str_starts_with((string) $property, 'filter')) {
            $this->resetPage();
        }
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', name: 'record-transfer');
    }

    public function saveTransfer(): void
    {
        $this->scope = app(HrScopeService::class);

        $data = $this->validate([
            'form.employee_id' => ['required', 'integer'],
            'form.to_org_unit_id' => ['required', 'integer'],
            'form.transfer_type' => ['required', 'string', 'in:'.implode(',', array_keys($this->typeOptions))],
            'form.effective_date' => ['required', 'date'],
            'form.reason' => ['nullable', 'string', 'max:1000'],
            'form.remarks' => ['nullable', 'string', 'max:1000'],
        ])['form'];

        // Only allow employees the HR can actually see.
        $employee = $this->scope
            ->applyToEmployeeQuery(Employee::query())
            ->whereKey($data['employee_id'])
            ->first();

        if ($employee === null) {
            $this->addError('form.employee_id', 'Select a valid employee.');

            return;
        }

        if ((int) $data['to_org_unit_id'] === (int) $employee->org_unit_id) {
            $this->addError('form.to_org_unit_id', 'The destination office is the same as the current office.');

            return;
        }

        $fromOrgUnitId = $employee->org_unit_id;
        $effectiveDate = $data['effective_date'];

        DB::transaction(function () use ($employee, $data, $fromOrgUnitId, $effectiveDate): void {
            TransferRequest::query()->create([
                'employee_id' => $employee->id,
                'from_org_unit_id' => $fromOrgUnitId,
                'to_org_unit_id' => $data['to_org_unit_id'],
                'initiated_by' => Auth::id(),
                'transfer_type' => $data['transfer_type'],
                'reason' => $data['reason'] ?: null,
                'requested_date' => now()->toDateString(),
                'effective_date' => $effectiveDate,
                'status' => 'completed',
                'remarks' => $data['remarks'] ?: null,
            ]);

            // Close the current open posting, then open a new one at the destination.
            PostingHistory::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->whereNull('to_date')
                ->update([
                    'to_date' => $effectiveDate,
                    'status' => 'closed',
                ]);

            $employee->update(['org_unit_id' => $data['to_org_unit_id']]);

            PostingHistory::query()->create([
                'employee_id' => $employee->id,
                'org_unit_id' => $data['to_org_unit_id'],
                'designation_id' => $employee->designation_id,
                'from_date' => $effectiveDate,
                'status' => 'active',
            ]);
        });

        session()->flash('status', $employee->full_name.' has been transferred successfully.');
        $this->resetForm();
        $this->dispatch('close-modal', name: 'record-transfer');
        $this->resetPage();
    }

    public function render()
    {
        $this->scope = app(HrScopeService::class);

        $transfers = $this->scope
            ->applyToEmployeeRelatedQuery(
                TransferRequest::query()
                    ->with([
                        'employee:id,full_name,employee_code',
                        'fromOrgUnit:id,name',
                        'toOrgUnit:id,name',
                        'initiatedBy:id,name',
                    ])
                    ->when($this->search !== '', fn ($q) => $q->whereHas(
                        'employee',
                        fn ($eq) => $eq->where('full_name', 'like', '%'.$this->search.'%')
                            ->orWhere('employee_code', 'like', '%'.$this->search.'%')
                    ))
                    ->when($this->filterType !== '', fn ($q) => $q->where('transfer_type', $this->filterType))
                    ->latest('id')
            )
            ->paginate(10);

        $employees = $this->scope
            ->applyToEmployeeQuery(Employee::query()->with('orgUnit:id,name'))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code', 'org_unit_id']);

        $orgUnits = OrgUnit::query()
            ->where('status', 'active')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.hr.transfers.index', [
            'transfers' => $transfers,
            'employees' => $employees,
            'orgUnits' => $orgUnits,
        ]);
    }

    private function resetForm(): void
    {
        $this->form = [
            'employee_id' => '',
            'to_org_unit_id' => '',
            'transfer_type' => 'administrative',
            'effective_date' => now()->format('Y-m-d'),
            'reason' => '',
            'remarks' => '',
        ];
        $this->resetErrorBag();
    }
}
