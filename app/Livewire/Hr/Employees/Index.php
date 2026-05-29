<?php

namespace App\Livewire\Hr\Employees;

use App\Models\DepartmentStream;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\OrgUnit;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterOrgUnit = '';

    public string $filterDepartmentStream = '';

    public string $filterEmploymentType = '';

    public string $filterStatus = '';

    public function updated($property): void
    {
        if (str_starts_with((string) $property, 'filter') || $property === 'search') {
            $this->resetPage();
        }
    }

    public function render()
    {
        $employees = Employee::query()
            ->with(['designation', 'departmentStream', 'employmentType', 'orgUnit'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('full_name', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$this->search.'%')
                        ->orWhere('pan_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterOrgUnit !== '', fn ($query) => $query->where('org_unit_id', $this->filterOrgUnit))
            ->when($this->filterDepartmentStream !== '', fn ($query) => $query->where('department_stream_id', $this->filterDepartmentStream))
            ->when($this->filterEmploymentType !== '', fn ($query) => $query->where('employment_type_id', $this->filterEmploymentType))
            ->when($this->filterStatus !== '', fn ($query) => $query->where('service_status', $this->filterStatus))
            ->latest('id')
            ->paginate(10);

        return view('livewire.hr.employees.index', [
            'employees' => $employees,
            'orgUnits' => OrgUnit::query()->orderBy('type')->orderBy('name')->get(),
            'departmentStreams' => DepartmentStream::query()->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::query()->orderBy('name')->get(),
        ]);
    }
}
