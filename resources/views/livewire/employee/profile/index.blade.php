<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="My Profile" description="Review your official details and complete your personal information." />

    @if (! $employee)
        <x-ui.alert variant="warning">
            Your login is not linked with an employee profile yet. Please contact HR.
        </x-ui.alert>
    @else
        @php
            $serviceRows = [
                'Employee Code' => $employee->employee_code,
                'Office / Unit' => $employee->orgUnit?->name,
                'Stream' => $employee->departmentStream?->name,
                'Employment Type' => $employee->employmentType?->name,
                'Cadre' => $employee->cadre?->name,
                'Designation' => $employee->designation?->name,
                'Joining Date' => $employee->joining_date?->format('d M Y'),
            ];

            $personalRows = [
                'Full Name' => $employee->full_name,
                'Father Name' => $employee->father_name,
                'Mother Name' => $employee->mother_name,
                'Date of Birth' => $employee->date_of_birth?->format('d M Y'),
                'Gender' => $employee->gender ? ucfirst($employee->gender) : null,
                'Blood Group' => $employee->blood_group,
                'PAN Number' => $employee->pan_number,
            ];
        @endphp

        <div class="grid gap-4 xl:grid-cols-3">
            <x-ui.stat-card label="Service Status" :value="ucfirst(str_replace('_', ' ', $employee->service_status))" hint="Controlled by HR" variant="success" />
            <x-ui.stat-card label="Stream" :value="$employee->departmentStream?->name ?? '-'" hint="PHED / JJM" variant="info" />
            <x-ui.stat-card label="Employment" :value="$employee->employmentType?->name ?? '-'" hint="Regular / contractual" />
        </div>

        @if (auth()->user()->must_change_password)
            <div class="mt-5">
                <x-ui.alert variant="warning" title="Temporary Password">
                    Your account is currently using a temporary password. Password change flow will be added next.
                </x-ui.alert>
            </div>
        @endif

        <div class="mt-5 border-b border-slate-200">
            <nav class="flex gap-1 overflow-x-auto">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        wire:click="setActiveTab('{{ $key }}')"
                        @class([
                            'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition',
                            'border-blue-700 text-blue-700' => $activeTab === $key,
                            'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' => $activeTab !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        @if ($activeTab === 'overview')
            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <x-ui.card title="Official Details">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ($serviceRows as $label => $value)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>

                <x-ui.card title="Personal Details">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ($personalRows as $label => $value)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>
            </div>
        @elseif ($activeTab === 'contacts')
            <div class="mt-5 space-y-5">
                @if (session('contact_status'))
                    <x-ui.alert variant="success">{{ session('contact_status') }}</x-ui.alert>
                @endif

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <x-ui.table :headers="['Type', 'Label', 'Details', 'Primary', '']">
                        @forelse ($employee->contacts as $contact)
                            @php
                                $address = collect([
                                    $contact->address_line_1,
                                    $contact->address_line_2,
                                    $contact->city,
                                    $contact->district,
                                    $contact->state,
                                    $contact->pincode,
                                ])->filter()->implode(', ');
                            @endphp

                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>{{ $contactTypeOptions[$contact->type] ?? $contact->type }}</x-ui.table.td>
                                <x-ui.table.td>{{ $contact->label ?: '-' }}</x-ui.table.td>
                                <x-ui.table.td>{{ $contact->value ?: ($address ?: '-') }}</x-ui.table.td>
                                <x-ui.table.td>
                                    @if ($contact->is_primary)
                                        <x-ui.badge variant="success">Primary</x-ui.badge>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex items-center gap-1">
                                        <x-ui.button wire:click="editContact({{ $contact->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                                        <x-ui.button wire:click="deleteContact({{ $contact->id }})" variant="ghost" size="sm">Delete</x-ui.button>
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-ui.empty-state
                                        title="No contacts added"
                                        description="Add mobile, email, address, or emergency contact details."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>

                    <x-ui.card :title="$editingContactId ? 'Edit Contact' : 'Add Contact'">
                        <form wire:submit="saveContact" class="space-y-4">
                            <x-ui.select
                                wire:model.live="contactForm.type"
                                label="Contact Type"
                                :options="$contactTypeOptions"
                                :error="$errors->first('contactForm.type')"
                                required
                            />

                            <x-ui.input wire:model="contactForm.label" label="Label" :error="$errors->first('contactForm.label')" />
                            <x-ui.input wire:model="contactForm.value" label="Contact Value" :error="$errors->first('contactForm.value')" />

                            @if (in_array($contactForm['type'], ['current_address', 'permanent_address'], true))
                                <x-ui.input wire:model="contactForm.address_line_1" label="Address Line 1" :error="$errors->first('contactForm.address_line_1')" required />
                                <x-ui.input wire:model="contactForm.address_line_2" label="Address Line 2" :error="$errors->first('contactForm.address_line_2')" />

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <x-ui.input wire:model="contactForm.city" label="City" :error="$errors->first('contactForm.city')" />
                                    <x-ui.input wire:model="contactForm.district" label="District" :error="$errors->first('contactForm.district')" />
                                    <x-ui.input wire:model="contactForm.state" label="State" :error="$errors->first('contactForm.state')" />
                                    <x-ui.input wire:model="contactForm.pincode" label="Pincode" :error="$errors->first('contactForm.pincode')" />
                                </div>
                            @endif

                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input wire:model="contactForm.is_primary" type="checkbox" class="h-4 w-4 border-slate-300 text-blue-700 focus:ring-blue-600">
                                Primary for this contact type
                            </label>

                            <div class="flex items-center justify-end gap-2">
                                @if ($editingContactId)
                                    <x-ui.button wire:click="resetContactForm" variant="outline">Cancel</x-ui.button>
                                @endif

                                <x-ui.button type="submit" variant="primary">
                                    {{ $editingContactId ? 'Update Contact' : 'Add Contact' }}
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        @elseif ($activeTab === 'family')
            @include('livewire.shared.employee-family-members')
        @else
            <div class="mt-5">
                <x-ui.card title="Documents">
                    <x-ui.empty-state title="Document upload will be added next" description="This is where Aadhaar, PAN, certificates, and appointment documents will go." />
                </x-ui.card>
            </div>
        @endif
    @endif
</section>
