<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header :title="$employee->full_name" :description="$employee->employee_code">
        <x-ui.button :href="route('hr.employees.edit', $employee)" variant="primary">Edit Employee</x-ui.button>
    </x-ui.page-header>

    @if (session('status'))
        <div class="mb-4">
            <x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
        </div>
    @endif

    @php
        $statusMap = [
            'active' => ['variant' => 'success', 'label' => 'Active'],
            'inactive' => ['variant' => 'default', 'label' => 'Inactive'],
            'on_leave' => ['variant' => 'warning', 'label' => 'On Leave'],
            'retired' => ['variant' => 'default', 'label' => 'Retired'],
            'suspended' => ['variant' => 'danger', 'label' => 'Suspended'],
        ];
        $status = $statusMap[$employee->service_status] ?? ['variant' => 'default', 'label' => $employee->service_status];

        $serviceRows = [
            'Office / Unit' => $employee->orgUnit?->name,
            'Stream' => $employee->departmentStream?->name,
            'Employment Type' => $employee->employmentType?->name,
            'Cadre' => $employee->cadre?->name,
            'Designation' => $employee->designation?->name,
            'Joining Date' => $employee->joining_date?->format('d M Y'),
            'Retirement Date' => $employee->retirement_date?->format('d M Y'),
        ];

        $personalRows = [
            'Father Name' => $employee->father_name,
            'Mother Name' => $employee->mother_name,
            'Date of Birth' => $employee->date_of_birth?->format('d M Y'),
            'Gender' => $employee->gender ? ucfirst($employee->gender) : null,
            'Blood Group' => $employee->blood_group,
            'Aadhaar Number' => $employee->aadhaar_number,
            'PAN Number' => $employee->pan_number,
            'Linked Login' => $employee->user?->email,
        ];
    @endphp

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.stat-card label="Service Status" :value="$status['label']" hint="Current employee state" :variant="$status['variant']" />
        <x-ui.stat-card label="Stream" :value="$employee->departmentStream?->name ?? '-'" hint="PHED / JJM classification" variant="info" />
        <x-ui.stat-card label="Employment" :value="$employee->employmentType?->name ?? '-'" hint="Regular / contractual classification" />
    </div>

    <div class="mt-5">
        @if (session('login_status'))
            <div class="mb-4">
                <x-ui.alert variant="success">{{ session('login_status') }}</x-ui.alert>
            </div>
        @endif

        @if ($generatedLogin)
            <div class="mb-4">
                <x-ui.alert variant="warning" title="Temporary Login Details">
                    Share these details with the employee now. The password will not be visible again after this page changes.
                    <span class="mt-3 block rounded-lg bg-white/70 p-3 font-mono text-xs leading-6">
                        Email: {{ $generatedLogin['email'] }}<br>
                        Password: {{ $generatedLogin['password'] }}
                    </span>
                </x-ui.alert>
            </div>
        @endif

        <x-ui.card title="Login Access" description="Employees sign in using only their login email.">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Employee Code</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $employee->employee_code }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Login Email</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ $employee->user?->email ?? 'Not created' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Password Status</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">
                            @if ($employee->user?->must_change_password)
                                Temporary
                            @elseif ($employee->user)
                                Set
                            @else
                                Not created
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if ($employee->user)
                        <x-ui.button wire:click="resetEmployeePassword" variant="outline">Reset Password</x-ui.button>
                    @else
                        <x-ui.button wire:click="createEmployeeLogin" variant="primary">Create Login</x-ui.button>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>

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
            <x-ui.card title="Service Details">
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

            <div class="xl:col-span-2">
                <x-ui.card title="Remarks">
                    <p class="text-sm leading-6 text-slate-600">{{ $employee->remarks ?: 'No remarks added yet.' }}</p>
                </x-ui.card>
            </div>
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

                        <x-ui.input
                            wire:model="contactForm.label"
                            label="Label"
                            placeholder="Office, personal, emergency"
                            :error="$errors->first('contactForm.label')"
                        />

                        <x-ui.input
                            wire:model="contactForm.value"
                            label="Contact Value"
                            placeholder="Mobile, email, or emergency contact"
                            :error="$errors->first('contactForm.value')"
                        />

                        @if (in_array($contactForm['type'], ['current_address', 'permanent_address'], true))
                            <x-ui.input
                                wire:model="contactForm.address_line_1"
                                label="Address Line 1"
                                :error="$errors->first('contactForm.address_line_1')"
                                required
                            />

                            <x-ui.input
                                wire:model="contactForm.address_line_2"
                                label="Address Line 2"
                                :error="$errors->first('contactForm.address_line_2')"
                            />

                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-ui.input
                                    wire:model="contactForm.city"
                                    label="City"
                                    :error="$errors->first('contactForm.city')"
                                />

                                <x-ui.input
                                    wire:model="contactForm.district"
                                    label="District"
                                    :error="$errors->first('contactForm.district')"
                                />

                                <x-ui.input
                                    wire:model="contactForm.state"
                                    label="State"
                                    :error="$errors->first('contactForm.state')"
                                />

                                <x-ui.input
                                    wire:model="contactForm.pincode"
                                    label="Pincode"
                                    :error="$errors->first('contactForm.pincode')"
                                />
                            </div>
                        @endif

                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input
                                wire:model="contactForm.is_primary"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                            >
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
    @elseif ($activeTab === 'salary')
        <div class="mt-5 space-y-5">
            @if (session('salary_status'))
                <x-ui.alert variant="success">{{ session('salary_status') }}</x-ui.alert>
            @endif

            @php
                $salaryStructure = $employee->salaryStructures->sortByDesc('id')->first();
            @endphp

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-5">
                    <x-ui.card title="Current Salary Details">
                        @if ($salaryStructure)
                            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Pay Level</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">
                                        {{ $salaryStructure->payLevel?->name ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Basic Salary</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">
                                        {{ number_format((float) $salaryStructure->basic_salary, 2) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Grade Pay</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">
                                        {{ $salaryStructure->grade_pay !== null ? number_format((float) $salaryStructure->grade_pay, 2) : '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective From</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">
                                        {{ $salaryStructure->effective_from?->format('d M Y') ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Effective To</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">
                                        {{ $salaryStructure->effective_to?->format('d M Y') ?? '-' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                                    <dd class="mt-1">
                                        <x-ui.badge :variant="$salaryStructure->status === 'active' ? 'success' : 'default'">
                                            {{ $salaryStatusOptions[$salaryStructure->status] ?? ucfirst($salaryStructure->status) }}
                                        </x-ui.badge>
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <x-ui.empty-state
                                title="No salary details added"
                                description="Add the employee pay level, basic salary, grade pay, and effective dates."
                            />
                        @endif
                    </x-ui.card>

                    <x-ui.table :headers="['Pay Level', 'Basic', 'Grade Pay', 'Effective', 'Status']">
                        @forelse ($employee->salaryStructures->sortByDesc('id') as $structure)
                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>{{ $structure->payLevel?->name ?? '-' }}</x-ui.table.td>
                                <x-ui.table.td>{{ number_format((float) $structure->basic_salary, 2) }}</x-ui.table.td>
                                <x-ui.table.td>{{ $structure->grade_pay !== null ? number_format((float) $structure->grade_pay, 2) : '-' }}</x-ui.table.td>
                                <x-ui.table.td>
                                    {{ $structure->effective_from?->format('d M Y') ?? '-' }}
                                    <span class="text-slate-400">to</span>
                                    {{ $structure->effective_to?->format('d M Y') ?? 'Present' }}
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :variant="$structure->status === 'active' ? 'success' : 'default'">
                                        {{ $salaryStatusOptions[$structure->status] ?? ucfirst($structure->status) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-ui.empty-state
                                        title="No salary history"
                                        description="Salary structure records will appear here."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>
                </div>

                <x-ui.card title="Update Salary">
                    <form wire:submit="saveSalary" class="space-y-4">
                        <x-ui.select
                            wire:model="salaryForm.pay_level_id"
                            label="Pay Level"
                            :options="$payLevelOptions"
                            :error="$errors->first('salaryForm.pay_level_id')"
                        />

                        <x-ui.input
                            wire:model="salaryForm.basic_salary"
                            type="number"
                            step="0.01"
                            label="Basic Salary"
                            :error="$errors->first('salaryForm.basic_salary')"
                            required
                        />

                        <x-ui.input
                            wire:model="salaryForm.grade_pay"
                            type="number"
                            step="0.01"
                            label="Grade Pay"
                            :error="$errors->first('salaryForm.grade_pay')"
                        />

                        <x-ui.input
                            wire:model="salaryForm.effective_from"
                            type="date"
                            label="Effective From"
                            :error="$errors->first('salaryForm.effective_from')"
                        />

                        <x-ui.input
                            wire:model="salaryForm.effective_to"
                            type="date"
                            label="Effective To"
                            :error="$errors->first('salaryForm.effective_to')"
                        />

                        <x-ui.select
                            wire:model="salaryForm.status"
                            label="Status"
                            :options="$salaryStatusOptions"
                            :error="$errors->first('salaryForm.status')"
                            required
                        />

                        <div class="flex justify-end">
                            <x-ui.button type="submit" variant="primary">Save Salary</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>
    @else
        <div class="mt-5">
            <x-ui.card :title="$tabs[$activeTab]">
                <x-ui.empty-state
                    :title="$tabs[$activeTab] . ' will be added next'"
                    description="This section is ready in the profile structure."
                />
            </x-ui.card>
        </div>
    @endif
</section>
