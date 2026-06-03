<?php

namespace App\Livewire\Hr\Employees;

use App\Models\Employee;
use App\Models\PayLevel;
use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    public Employee $employee;

    public string $activeTab = 'overview';

    public ?int $editingContactId = null;

    public ?int $editingFamilyMemberId = null;

    public ?int $editingSalaryComponentId = null;

    public ?array $generatedLogin = null;

    public array $contactForm = [
        'type' => 'mobile',
        'label' => '',
        'value' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'district' => '',
        'state' => 'Assam',
        'pincode' => '',
        'is_primary' => false,
    ];

    public array $familyForm = [
        'name' => '',
        'relationship' => '',
        'date_of_birth' => '',
        'gender' => '',
        'mobile' => '',
        'occupation' => '',
        'is_dependent' => false,
        'is_nominee' => false,
        'nominee_share' => '',
    ];

    public array $salaryComponentForm = [
        'pay_level_id' => '',
        'salary_component_id' => '',
        'amount' => '',
        'percentage_rate' => '',
        'grade_pay' => '',
        'calculation_type' => 'fixed',
        'calculation_base' => '',
        'formula' => '',
        'effective_from' => '',
        'effective_to' => '',
        'salary_structure_status' => 'active',
        'status' => 'active',
    ];

    public function mount(Employee $employee): void
    {
        $this->employee = $employee->load([
            'cadre',
            'contacts',
            'departmentStream',
            'designation',
            'employmentType',
            'familyMembers',
            'orgUnit',
            'salaryStructures.employeeSalaryComponents.salaryComponent',
            'salaryStructures.payLevel.payMatrix',
            'user',
        ]);

        $this->resetSalaryComponentForm();
    }

    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab;
        }
    }

    public function saveContact(): void
    {
        $data = $this->validateContactForm();

        if (($data['is_primary'] ?? false) === true) {
            $this->employee
                ->contacts()
                ->where('type', $data['type'])
                ->when($this->editingContactId, fn ($query) => $query->whereKeyNot($this->editingContactId))
                ->update(['is_primary' => false]);
        }

        if ($this->editingContactId) {
            $contact = $this->employee->contacts()->whereKey($this->editingContactId)->firstOrFail();
            $contact->update($data);
            session()->flash('contact_status', 'Contact updated successfully.');
        } else {
            $this->employee->contacts()->create($data);
            session()->flash('contact_status', 'Contact added successfully.');
        }

        $this->resetContactForm();
        $this->employee->load('contacts');
    }

    public function saveFamilyMember(): void
    {
        $data = $this->validateFamilyForm();

        if ($this->editingFamilyMemberId) {
            $familyMember = $this->employee->familyMembers()->whereKey($this->editingFamilyMemberId)->firstOrFail();
            $familyMember->update($data);
            session()->flash('family_status', 'Family member updated successfully.');
        } else {
            $this->employee->familyMembers()->create($data);
            session()->flash('family_status', 'Family member added successfully.');
        }

        $this->resetFamilyForm();
        $this->employee->load('familyMembers');
    }

    public function saveSalaryComponent(): void
    {
        $currentSalaryStructure = $this->currentSalaryStructure();
        $data = $this->validateSalaryComponentForm($currentSalaryStructure?->id);
        $salaryStructure = $this->saveSalaryStructureFromComponent($data, $currentSalaryStructure);

        $componentData = $this->salaryComponentData($data, $salaryStructure);

        if ($this->editingSalaryComponentId) {
            $component = $salaryStructure
                ->employeeSalaryComponents()
                ->whereKey($this->editingSalaryComponentId)
                ->firstOrFail();

            $component->update($componentData);
            session()->flash('salary_component_status', 'Salary component updated successfully.');
        } else {
            $salaryStructure->employeeSalaryComponents()->create($componentData);
            session()->flash('salary_component_status', 'Salary component added successfully.');
        }

        $this->employee->load([
            'salaryStructures.employeeSalaryComponents.salaryComponent',
            'salaryStructures.payLevel.payMatrix',
        ]);
        $this->resetSalaryComponentForm();
    }

    public function updatedSalaryComponentFormSalaryComponentId($value): void
    {
        $component = SalaryComponent::query()->find($value);

        if (! $component) {
            return;
        }

        $this->salaryComponentForm['calculation_type'] = $component->calculation_type;

        if ($component->calculation_type === 'percentage') {
            $this->salaryComponentForm['amount'] = '';

            return;
        }

        if ($this->salaryComponentForm['amount'] === '') {
            $this->salaryComponentForm['amount'] = $component->default_amount;
        }
    }

    public function createEmployeeLogin(): void
    {
        $this->employee->refresh();

        if ($this->employee->user) {
            session()->flash('login_status', 'This employee already has a login account.');

            return;
        }

        $password = $this->generateTemporaryPassword();

        $user = User::query()->create([
            'name' => $this->employee->full_name,
            'email' => $this->employeeLoginEmail(),
            'password' => $password,
            'is_admin' => false,
            'is_hr' => false,
            'status' => 'active',
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $this->employee->update(['user_id' => $user->id]);
        $this->employee->load('user');

        $this->generatedLogin = [
            'email' => $user->email,
            'password' => $password,
        ];

        session()->flash('login_status', 'Employee login created successfully.');
    }

    public function resetEmployeePassword(): void
    {
        $this->employee->load('user');

        if (! $this->employee->user) {
            $this->createEmployeeLogin();

            return;
        }

        $password = $this->generateTemporaryPassword();

        $this->employee->user->update([
            'password' => $password,
            'must_change_password' => true,
            'status' => 'active',
        ]);

        $this->generatedLogin = [
            'email' => $this->employee->user->email,
            'password' => $password,
        ];

        session()->flash('login_status', 'Temporary password generated successfully.');
    }

    public function editContact(int $contactId): void
    {
        $contact = $this->employee->contacts()->whereKey($contactId)->firstOrFail();

        $this->editingContactId = $contact->id;
        $this->contactForm = [
            'type' => $contact->type,
            'label' => $contact->label ?? '',
            'value' => $contact->value ?? '',
            'address_line_1' => $contact->address_line_1 ?? '',
            'address_line_2' => $contact->address_line_2 ?? '',
            'city' => $contact->city ?? '',
            'district' => $contact->district ?? '',
            'state' => $contact->state ?? '',
            'pincode' => $contact->pincode ?? '',
            'is_primary' => $contact->is_primary,
        ];
    }

    public function editFamilyMember(int $familyMemberId): void
    {
        $familyMember = $this->employee->familyMembers()->whereKey($familyMemberId)->firstOrFail();

        $this->editingFamilyMemberId = $familyMember->id;
        $this->familyForm = [
            'name' => $familyMember->name,
            'relationship' => $familyMember->relationship,
            'date_of_birth' => $familyMember->date_of_birth?->format('Y-m-d') ?? '',
            'gender' => $familyMember->gender ?? '',
            'mobile' => $familyMember->mobile ?? '',
            'occupation' => $familyMember->occupation ?? '',
            'is_dependent' => $familyMember->is_dependent,
            'is_nominee' => $familyMember->is_nominee,
            'nominee_share' => $familyMember->nominee_share ?? '',
        ];
    }

    public function deleteContact(int $contactId): void
    {
        $this->employee->contacts()->whereKey($contactId)->delete();

        if ($this->editingContactId === $contactId) {
            $this->resetContactForm();
        }

        $this->employee->load('contacts');
        session()->flash('contact_status', 'Contact deleted successfully.');
    }

    public function editSalaryComponent(int $salaryComponentId): void
    {
        $salaryStructure = $this->currentSalaryStructure();

        if (! $salaryStructure) {
            return;
        }

        $component = $salaryStructure
            ->employeeSalaryComponents()
            ->whereKey($salaryComponentId)
            ->firstOrFail();

        $this->editingSalaryComponentId = $component->id;
        $this->salaryComponentForm = [
            'pay_level_id' => $salaryStructure->pay_level_id ? (string) $salaryStructure->pay_level_id : '',
            'salary_component_id' => $component->salary_component_id ? (string) $component->salary_component_id : '',
            'amount' => $component->amount ?? '',
            'percentage_rate' => $component->percentage_rate ?? '',
            'grade_pay' => $this->isBasicSalaryComponent($component->salaryComponent) ? ($salaryStructure->grade_pay ?? '') : '',
            'calculation_type' => $component->calculation_type,
            'calculation_base' => $component->calculation_base ?? '',
            'formula' => $component->formula ?? '',
            'effective_from' => $salaryStructure->effective_from?->format('Y-m-d') ?? '',
            'effective_to' => $salaryStructure->effective_to?->format('Y-m-d') ?? '',
            'salary_structure_status' => $salaryStructure->status ?? 'active',
            'status' => $component->status,
        ];
    }

    public function deleteSalaryComponent(int $salaryComponentId): void
    {
        $salaryStructure = $this->currentSalaryStructure();

        if (! $salaryStructure) {
            return;
        }

        $component = $salaryStructure
            ->employeeSalaryComponents()
            ->with('salaryComponent')
            ->whereKey($salaryComponentId)
            ->firstOrFail();

        $deletingBasicSalary = $this->isBasicSalaryComponent($component->salaryComponent);

        $component->delete();

        if ($deletingBasicSalary) {
            $salaryStructure->update([
                'basic_salary' => 0,
                'grade_pay' => null,
            ]);
        }

        if ($this->editingSalaryComponentId === $salaryComponentId) {
            $this->resetSalaryComponentForm();
        }

        $this->employee->load([
            'salaryStructures.employeeSalaryComponents.salaryComponent',
            'salaryStructures.payLevel.payMatrix',
        ]);
        session()->flash('salary_component_status', 'Salary component deleted successfully.');
    }

    public function deleteFamilyMember(int $familyMemberId): void
    {
        $this->employee->familyMembers()->whereKey($familyMemberId)->delete();

        if ($this->editingFamilyMemberId === $familyMemberId) {
            $this->resetFamilyForm();
        }

        $this->employee->load('familyMembers');
        session()->flash('family_status', 'Family member deleted successfully.');
    }

    public function resetContactForm(): void
    {
        $this->editingContactId = null;
        $this->resetErrorBag();
        $this->contactForm = [
            'type' => 'mobile',
            'label' => '',
            'value' => '',
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'district' => '',
            'state' => 'Assam',
            'pincode' => '',
            'is_primary' => false,
        ];
    }

    public function resetFamilyForm(): void
    {
        $this->editingFamilyMemberId = null;
        $this->resetErrorBag();
        $this->familyForm = [
            'name' => '',
            'relationship' => '',
            'date_of_birth' => '',
            'gender' => '',
            'mobile' => '',
            'occupation' => '',
            'is_dependent' => false,
            'is_nominee' => false,
            'nominee_share' => '',
        ];
    }

    public function resetSalaryComponentForm(): void
    {
        $salaryStructure = $this->currentSalaryStructure();
        $this->editingSalaryComponentId = null;
        $this->resetErrorBag();
        $this->salaryComponentForm = [
            'pay_level_id' => $salaryStructure?->pay_level_id ? (string) $salaryStructure->pay_level_id : '',
            'salary_component_id' => '',
            'amount' => '',
            'percentage_rate' => '',
            'grade_pay' => '',
            'calculation_type' => 'fixed',
            'calculation_base' => '',
            'formula' => '',
            'effective_from' => $salaryStructure?->effective_from?->format('Y-m-d') ?? '',
            'effective_to' => $salaryStructure?->effective_to?->format('Y-m-d') ?? '',
            'salary_structure_status' => $salaryStructure?->status ?? 'active',
            'status' => 'active',
        ];
    }

    public function render()
    {
        return view('livewire.hr.employees.show', [
            'calculationBaseOptions' => $this->calculationBaseOptions(),
            'calculationTypeOptions' => $this->calculationTypeOptions(),
            'contactTypeOptions' => $this->contactTypeOptions(),
            'familyGenderOptions' => $this->familyGenderOptions(),
            'payLevelOptions' => $this->payLevelOptions(),
            'relationshipOptions' => $this->relationshipOptions(),
            'salaryComponentOptions' => $this->salaryComponentOptions(),
            'salaryStatusOptions' => $this->salaryStatusOptions(),
            'selectedCalculationTypeIsPercentage' => $this->selectedCalculationTypeIsPercentage(),
            'selectedSalaryComponentIsBasic' => $this->selectedSalaryComponentIsBasic(),
            'tabs' => $this->tabs(),
        ]);
    }

    private function validateContactForm(): array
    {
        $validated = $this->validate([
            'contactForm.type' => ['required', Rule::in(array_keys($this->contactTypeOptions()))],
            'contactForm.label' => ['nullable', 'string', 'max:100'],
            'contactForm.value' => ['nullable', 'string', 'max:255'],
            'contactForm.address_line_1' => ['nullable', 'string', 'max:255'],
            'contactForm.address_line_2' => ['nullable', 'string', 'max:255'],
            'contactForm.city' => ['nullable', 'string', 'max:100'],
            'contactForm.district' => ['nullable', 'string', 'max:100'],
            'contactForm.state' => ['nullable', 'string', 'max:100'],
            'contactForm.pincode' => ['nullable', 'string', 'max:20'],
            'contactForm.is_primary' => ['boolean'],
        ])['contactForm'];

        if ($this->requiresValue($validated['type']) && blank($validated['value'])) {
            throw ValidationException::withMessages([
                'contactForm.value' => 'The contact value is required for this contact type.',
            ]);
        }

        if ($this->requiresAddress($validated['type']) && blank($validated['address_line_1'])) {
            throw ValidationException::withMessages([
                'contactForm.address_line_1' => 'The address line is required for this contact type.',
            ]);
        }

        return array_map(fn ($value) => $value === '' ? null : $value, $validated);
    }

    private function validateFamilyForm(): array
    {
        $validated = $this->validate([
            'familyForm.name' => ['required', 'string', 'max:150'],
            'familyForm.relationship' => ['required', Rule::in(array_keys($this->relationshipOptions()))],
            'familyForm.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'familyForm.gender' => ['nullable', Rule::in(array_keys($this->familyGenderOptions()))],
            'familyForm.mobile' => ['nullable', 'string', 'max:20'],
            'familyForm.occupation' => ['nullable', 'string', 'max:150'],
            'familyForm.is_dependent' => ['boolean'],
            'familyForm.is_nominee' => ['boolean'],
            'familyForm.nominee_share' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ])['familyForm'];

        if (($validated['is_nominee'] ?? false) && blank($validated['nominee_share'])) {
            throw ValidationException::withMessages([
                'familyForm.nominee_share' => 'The nominee share is required when this member is a nominee.',
            ]);
        }

        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        if (! ($validated['is_nominee'] ?? false)) {
            $validated['nominee_share'] = null;
        }

        return $validated;
    }

    private function validateSalaryComponentForm(?int $salaryStructureId): array
    {
        $salaryComponentIdRules = [
            'required',
            'integer',
            'exists:salary_components,id',
        ];

        if ($salaryStructureId) {
            $salaryComponentIdRules[] = Rule::unique('employee_salary_components', 'salary_component_id')
                ->where('salary_structure_id', $salaryStructureId)
                ->ignore($this->editingSalaryComponentId);
        }

        $validated = $this->validate([
            'salaryComponentForm.pay_level_id' => ['nullable', 'integer', 'exists:pay_levels,id'],
            'salaryComponentForm.salary_component_id' => $salaryComponentIdRules,
            'salaryComponentForm.amount' => ['nullable', 'numeric', 'min:0'],
            'salaryComponentForm.percentage_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'salaryComponentForm.grade_pay' => ['nullable', 'numeric', 'min:0'],
            'salaryComponentForm.calculation_type' => ['required', Rule::in(array_keys($this->calculationTypeOptions()))],
            'salaryComponentForm.calculation_base' => ['nullable', Rule::in(array_keys($this->calculationBaseOptions()))],
            'salaryComponentForm.formula' => ['nullable', 'string', 'max:2000'],
            'salaryComponentForm.effective_from' => ['nullable', 'date'],
            'salaryComponentForm.effective_to' => ['nullable', 'date', 'after_or_equal:salaryComponentForm.effective_from'],
            'salaryComponentForm.salary_structure_status' => ['required', Rule::in(array_keys($this->salaryStatusOptions()))],
            'salaryComponentForm.status' => ['required', Rule::in(array_keys($this->salaryStatusOptions()))],
        ], [
            'salaryComponentForm.salary_component_id.unique' => 'This component is already added to the current salary structure.',
        ])['salaryComponentForm'];

        if ($validated['calculation_type'] === 'percentage') {
            if (blank($validated['percentage_rate'])) {
                throw ValidationException::withMessages([
                    'salaryComponentForm.percentage_rate' => 'The percentage rate is required for percentage components.',
                ]);
            }

            if (blank($validated['calculation_base'])) {
                throw ValidationException::withMessages([
                    'salaryComponentForm.calculation_base' => 'Select what this percentage should be calculated on.',
                ]);
            }

            $validated['amount'] = null;
        } else {
            if (blank($validated['amount'])) {
                throw ValidationException::withMessages([
                    'salaryComponentForm.amount' => 'The amount is required for this calculation type.',
                ]);
            }

            $validated['percentage_rate'] = null;
            $validated['calculation_base'] = null;
        }

        return array_map(fn ($value) => $value === '' ? null : $value, $validated);
    }

    private function requiresValue(string $type): bool
    {
        return in_array($type, ['mobile', 'email', 'emergency_contact'], true);
    }

    private function requiresAddress(string $type): bool
    {
        return in_array($type, ['current_address', 'permanent_address'], true);
    }

    private function contactTypeOptions(): array
    {
        return [
            'mobile' => 'Mobile',
            'email' => 'Email',
            'current_address' => 'Current Address',
            'permanent_address' => 'Permanent Address',
            'emergency_contact' => 'Emergency Contact',
        ];
    }

    private function relationshipOptions(): array
    {
        return [
            'spouse' => 'Spouse',
            'father' => 'Father',
            'mother' => 'Mother',
            'son' => 'Son',
            'daughter' => 'Daughter',
            'brother' => 'Brother',
            'sister' => 'Sister',
            'guardian' => 'Guardian',
            'other' => 'Other',
        ];
    }

    private function familyGenderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
        ];
    }

    private function calculationTypeOptions(): array
    {
        return [
            'fixed' => 'Fixed',
            'percentage' => 'Percentage',
            'formula' => 'Formula',
        ];
    }

    private function calculationBaseOptions(): array
    {
        return [
            'basic_salary' => 'Basic Salary',
            'basic_plus_grade_pay' => 'Basic Salary + Grade Pay',
            'gross_earnings' => 'Gross Earnings',
        ];
    }

    private function salaryStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    private function payLevelOptions(): array
    {
        return PayLevel::query()
            ->with('payMatrix')
            ->where('status', 'active')
            ->orderBy('level_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (PayLevel $payLevel): array {
                $matrixCode = $payLevel->payMatrix?->code ? $payLevel->payMatrix->code.' - ' : '';

                return [$payLevel->id => $matrixCode.$payLevel->name.' ('.$payLevel->code.')'];
            })
            ->all();
    }

    private function salaryComponentOptions(): array
    {
        return SalaryComponent::query()
            ->where('status', 'active')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (SalaryComponent $component): array => [
                $component->id => $component->name.' ('.$component->code.', '.ucfirst($component->type).')',
            ])
            ->all();
    }

    private function tabs(): array
    {
        return [
            'overview' => 'Overview',
            'contacts' => 'Contacts',
            'family' => 'Family',
            'salary' => 'Salary',
            'qualifications' => 'Qualifications',
            'experience' => 'Experience',
            'documents' => 'Documents',
            'posting' => 'Posting History',
        ];
    }

    private function employeeLoginEmail(): string
    {
        $emailContact = $this->employee
            ->contacts()
            ->where('type', 'email')
            ->whereNotNull('value')
            ->orderByDesc('is_primary')
            ->first();

        $email = strtolower((string) $emailContact?->value);

        if ($email !== '' && ! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        $baseEmail = strtolower($this->employee->employee_code).'@employee.jjmbrain.local';
        $candidate = $baseEmail;
        $counter = 2;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = strtolower($this->employee->employee_code).'-'.$counter.'@employee.jjmbrain.local';
            $counter++;
        }

        return $candidate;
    }

    private function generateTemporaryPassword(): string
    {
        return 'Jjm@'.Str::upper(Str::random(4)).random_int(1000, 9999);
    }

    private function saveSalaryStructureFromComponent(array $data, ?SalaryStructure $salaryStructure): SalaryStructure
    {
        $salaryStructureData = [
            'pay_level_id' => $data['pay_level_id'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'],
            'status' => $data['salary_structure_status'],
        ];

        $component = SalaryComponent::query()->find($data['salary_component_id']);

        if ($this->isBasicSalaryComponent($component)) {
            $salaryStructureData['basic_salary'] = $this->calculateComponentAmount($data, $salaryStructure);
            $salaryStructureData['grade_pay'] = $data['grade_pay'];
        } elseif (! $salaryStructure) {
            $salaryStructureData['basic_salary'] = 0;
            $salaryStructureData['grade_pay'] = null;
        }

        if ($salaryStructure) {
            $salaryStructure->update($salaryStructureData);

            return $salaryStructure->refresh();
        }

        return $this->employee->salaryStructures()->create($salaryStructureData);
    }

    private function salaryComponentData(array $data, SalaryStructure $salaryStructure): array
    {
        return [
            'salary_component_id' => $data['salary_component_id'],
            'amount' => $this->calculateComponentAmount($data, $salaryStructure),
            'percentage_rate' => $data['percentage_rate'],
            'calculation_type' => $data['calculation_type'],
            'calculation_base' => $data['calculation_base'],
            'formula' => $data['formula'],
            'status' => $data['status'],
        ];
    }

    private function calculateComponentAmount(array $data, ?SalaryStructure $salaryStructure): float
    {
        if ($data['calculation_type'] !== 'percentage') {
            return (float) ($data['amount'] ?? 0);
        }

        $baseAmount = $this->calculationBaseAmount($data['calculation_base'], $salaryStructure);

        return round($baseAmount * ((float) $data['percentage_rate'] / 100), 2);
    }

    private function calculationBaseAmount(?string $base, ?SalaryStructure $salaryStructure): float
    {
        if (! $salaryStructure) {
            return 0;
        }

        return match ($base) {
            'basic_salary' => (float) $salaryStructure->basic_salary,
            'basic_plus_grade_pay' => (float) $salaryStructure->basic_salary + (float) $salaryStructure->grade_pay,
            'gross_earnings' => $this->grossEarnings($salaryStructure),
            default => 0,
        };
    }

    private function grossEarnings(SalaryStructure $salaryStructure): float
    {
        $earningComponents = $salaryStructure
            ->employeeSalaryComponents()
            ->with('salaryComponent')
            ->when($this->editingSalaryComponentId, fn ($query) => $query->whereKeyNot($this->editingSalaryComponentId))
            ->where('status', 'active')
            ->get();

        $total = $earningComponents
            ->filter(fn ($component) => $component->salaryComponent?->type !== 'deduction' && ! $component->salaryComponent?->is_deduction)
            ->sum(fn ($component) => (float) $component->amount);

        $hasBasicComponent = $earningComponents
            ->contains(fn ($component) => $this->isBasicSalaryComponent($component->salaryComponent));

        if (! $hasBasicComponent) {
            $total += (float) $salaryStructure->basic_salary;
        }

        return $total;
    }

    private function selectedSalaryComponentIsBasic(): bool
    {
        if ($this->salaryComponentForm['salary_component_id'] === '') {
            return false;
        }

        return $this->isBasicSalaryComponent(
            SalaryComponent::query()->find($this->salaryComponentForm['salary_component_id'])
        );
    }

    private function selectedCalculationTypeIsPercentage(): bool
    {
        return $this->salaryComponentForm['calculation_type'] === 'percentage';
    }

    private function isBasicSalaryComponent(?SalaryComponent $component): bool
    {
        if (! $component) {
            return false;
        }

        return strtoupper($component->code) === 'BASIC'
            || str_contains(strtolower($component->name), 'basic');
    }

    private function currentSalaryStructure(): ?SalaryStructure
    {
        return $this->employee
            ->salaryStructures
            ->sortByDesc('id')
            ->first();
    }
}
