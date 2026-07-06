<?php

namespace App\Livewire\Hr\Employees;

use App\Models\Cadre;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\OrgUnit;
use App\Services\Hr\HrScopeService;
use App\Services\Hr\OrgUnitStreamService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    public Employee $employee;

    public array $form = [];

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;

        $this->form = [
            'employee_code' => $employee->employee_code,
            'full_name' => $employee->full_name,
            'father_name' => $employee->father_name,
            'mother_name' => $employee->mother_name,
            'date_of_birth' => $employee->date_of_birth?->format('Y-m-d') ?? '',
            'gender' => $employee->gender,
            'blood_group' => $employee->blood_group,
            'aadhaar_number' => $employee->aadhaar_number,
            'pan_number' => $employee->pan_number,
            'org_unit_id' => $employee->org_unit_id,
            'department_stream_id' => $employee->department_stream_id,
            'staff_category_id' => $employee->staff_category_id,
            'employment_type_id' => $employee->employment_type_id,
            'cadre_id' => $employee->cadre_id,
            'designation_id' => $employee->designation_id,
            'joining_date' => $employee->joining_date?->format('Y-m-d') ?? '',
            'retirement_date' => $employee->retirement_date?->format('Y-m-d') ?? '',
            'service_status' => $employee->service_status,
            'remarks' => $employee->remarks,
        ];
    }

    public function save()
    {
        $data = $this->validate($this->rules())['form'];
        $data = $this->normalize($data);

        $this->employee->update($data);

        session()->flash('status', 'Employee updated successfully.');

        return redirect()->route('hr.employees.show', $this->employee);
    }

    public function updatedFormOrgUnitId(): void
    {
        $this->clearUnavailableDepartmentStream();
    }

    public function render()
    {
        return view('livewire.hr.employees.edit', $this->formData());
    }

    private function rules(): array
    {
        return [
            'form.employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($this->employee)],
            'form.full_name' => ['required', 'string', 'max:255'],
            'form.father_name' => ['nullable', 'string', 'max:255'],
            'form.mother_name' => ['nullable', 'string', 'max:255'],
            'form.date_of_birth' => ['nullable', 'date'],
            'form.gender' => ['nullable', 'string', 'max:30'],
            'form.blood_group' => ['nullable', 'string', 'max:10'],
            'form.aadhaar_number' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'aadhaar_number')->ignore($this->employee)],
            'form.pan_number' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'pan_number')->ignore($this->employee)],
            'form.org_unit_id' => ['required', $this->orgUnitRule()],
            'form.department_stream_id' => ['required', $this->departmentStreamRule()],
            'form.staff_category_id' => ['nullable', 'exists:staff_categories,id'],
            'form.employment_type_id' => ['nullable', 'exists:employment_types,id'],
            'form.cadre_id' => ['nullable', 'exists:cadres,id'],
            'form.designation_id' => ['nullable', 'exists:designations,id'],
            'form.joining_date' => ['nullable', 'date'],
            'form.retirement_date' => ['nullable', 'date', 'after_or_equal:form.joining_date'],
            'form.service_status' => ['required', Rule::in(['active', 'inactive', 'on_leave', 'retired', 'suspended'])],
            'form.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function formData(): array
    {
        return [
            'orgUnitOptions' => $this->orgUnitOptions(),
            'departmentStreamOptions' => app(OrgUnitStreamService::class)->mappedActiveOptionsFor($this->form['org_unit_id'] ?? null),
            'departmentStreamPlaceholder' => $this->departmentStreamPlaceholder(),
            'staffCategoryOptions' => \App\Models\StaffCategory::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'employmentTypeOptions' => EmploymentType::query()->orderBy('name')->pluck('name', 'id')->all(),
            'cadreOptions' => Cadre::query()->orderBy('name')->pluck('name', 'id')->all(),
            'designationOptions' => Designation::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => $this->statusOptions(),
        ];
    }

    private function normalize(array $data): array
    {
        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);

        if (isset($data['pan_number'])) {
            $data['pan_number'] = strtoupper((string) $data['pan_number']);
        }

        return $data;
    }

    /**
     * Offices the current HR may assign to: their scoped org units (own unit,
     * plus child units when include_child_units is set). Unrestricted HR see all.
     * The employee's current office is always included so an edit never breaks.
     *
     * @return array<int, string>
     */
    private function orgUnitOptions(): array
    {
        $query = OrgUnit::query()->orderBy('type')->orderBy('name');

        $scopedIds = $this->scopedOrgUnitIds();
        if ($scopedIds !== null) {
            $query->whereIn('id', $scopedIds);
        }

        return $query->pluck('name', 'id')->all();
    }

    private function orgUnitRule()
    {
        $scopedIds = $this->scopedOrgUnitIds();

        return $scopedIds === null
            ? Rule::exists('org_units', 'id')
            : Rule::in($scopedIds);
    }

    /**
     * Scoped org unit ids, always including the employee's current office.
     *
     * @return array<int, int>|null
     */
    private function scopedOrgUnitIds(): ?array
    {
        $scoped = app(HrScopeService::class)->scopedOrgUnitIds();

        if ($scoped === null) {
            return null;
        }

        return $scoped
            ->when($this->employee->org_unit_id !== null, fn ($ids) => $ids->push($this->employee->org_unit_id))
            ->unique()
            ->values()
            ->all();
    }

    private function departmentStreamRule()
    {
        // Strict: the stream must be one explicitly mapped to the selected office.
        return Rule::in(app(OrgUnitStreamService::class)->mappedActiveIdsFor($this->form['org_unit_id'] ?? null));
    }

    private function departmentStreamPlaceholder(): string
    {
        if (blank($this->form['org_unit_id'] ?? null)) {
            return 'Select an office first';
        }

        return app(OrgUnitStreamService::class)->mappedActiveOptionsFor($this->form['org_unit_id'])
            ? 'Select stream'
            : 'No streams set for this unit';
    }

    private function clearUnavailableDepartmentStream(): void
    {
        if (! app(OrgUnitStreamService::class)->isStreamMappedTo(
            $this->form['org_unit_id'] ?? null,
            $this->form['department_stream_id'] ?? null,
        )) {
            $this->form['department_stream_id'] = '';
        }
    }

    private function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'on_leave' => 'On Leave',
            'retired' => 'Retired',
            'suspended' => 'Suspended',
        ];
    }
}
