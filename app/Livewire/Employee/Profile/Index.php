<?php

namespace App\Livewire\Employee\Profile;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public ?Employee $employee = null;

    public string $activeTab = 'overview';

    public ?int $editingContactId = null;

    public ?int $editingFamilyMemberId = null;

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

    public function mount(): void
    {
        $this->employee = Auth::user()
            ->employee()
            ->with(['cadre', 'contacts', 'departmentStream', 'designation', 'employmentType', 'familyMembers', 'orgUnit'])
            ->first();
    }

    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab;
        }
    }

    public function saveContact(): void
    {
        if (! $this->employee) {
            return;
        }

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
        if (! $this->employee) {
            return;
        }

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

    public function editContact(int $contactId): void
    {
        if (! $this->employee) {
            return;
        }

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
        if (! $this->employee) {
            return;
        }

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
        if (! $this->employee) {
            return;
        }

        $this->employee->contacts()->whereKey($contactId)->delete();

        if ($this->editingContactId === $contactId) {
            $this->resetContactForm();
        }

        $this->employee->load('contacts');
        session()->flash('contact_status', 'Contact deleted successfully.');
    }

    public function deleteFamilyMember(int $familyMemberId): void
    {
        if (! $this->employee) {
            return;
        }

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

    public function render()
    {
        return view('livewire.employee.profile.index', [
            'contactTypeOptions' => $this->contactTypeOptions(),
            'familyGenderOptions' => $this->familyGenderOptions(),
            'relationshipOptions' => $this->relationshipOptions(),
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

    private function tabs(): array
    {
        return [
            'overview' => 'Overview',
            'contacts' => 'Contacts',
            'family' => 'Family',
            'bank' => 'Bank Details',
            'documents' => 'Documents',
        ];
    }
}
