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
                    <span class="mt-3 block bg-white/70 p-3 font-mono text-xs leading-6">
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
                                class="h-4 w-4 border-slate-300 text-blue-700 focus:ring-blue-600"
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
                $salaryStructure = $salaryStructure ?? null;
            @endphp

            @if (session('salary_component_status'))
                <x-ui.alert variant="success">{{ session('salary_component_status') }}</x-ui.alert>
            @endif

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <x-ui.card title="Salary Components">
                    <div class="mb-5 grid gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pay Level</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ $salaryStructure?->payLevel?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross Earnings</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ number_format($payrollPreview['gross'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Net Pay</p>
                            <p class="mt-1 text-sm font-medium text-slate-900">{{ number_format($payrollPreview['net'], 2) }}</p>
                        </div>
                    </div>

                    <x-ui.table :headers="['Component', 'Type', 'Calculation', 'Amount', 'Status', '']">
                        @forelse ($salaryComponents as $salaryComponentItem)
                            @php
                                $componentType = $salaryComponentItem->salaryComponent?->type;
                                $isDeduction = $componentType === 'deduction' || $salaryComponentItem->salaryComponent?->is_deduction;
                                $typeVariant = $isDeduction ? 'warning' : 'success';
                            @endphp

                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>
                                    <span class="font-medium text-slate-900">{{ $salaryComponentItem->salaryComponent?->name ?? '-' }}</span>
                                    <p class="text-xs text-slate-400">{{ $salaryComponentItem->salaryComponent?->code ?? '-' }}</p>
                                    @if (($salaryComponentItem->salaryComponent?->code === 'BASIC' || str_contains(strtolower((string) $salaryComponentItem->salaryComponent?->name), 'basic')) && $salaryStructure?->grade_pay !== null)
                                        <p class="mt-1 text-xs text-slate-500">Grade Pay: {{ number_format((float) $salaryStructure->grade_pay, 2) }}</p>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if ($componentType)
                                        <x-ui.badge :variant="$typeVariant">{{ ucfirst($componentType) }}</x-ui.badge>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span>{{ ucfirst($salaryComponentItem->calculation_type) }}</span>
                                    @if ($salaryComponentItem->calculation_type === 'percentage')
                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ number_format((float) $salaryComponentItem->percentage_rate, 2) }}%
                                            on {{ $calculationBaseOptions[$salaryComponentItem->calculation_base] ?? 'selected base' }}
                                        </p>
                                    @endif
                                    @if ($salaryComponentItem->formula)
                                        <p class="mt-1 max-w-xs truncate text-xs text-slate-400">{{ $salaryComponentItem->formula }}</p>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>{{ number_format((float) $salaryComponentItem->amount, 2) }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :variant="$salaryComponentItem->status === 'active' ? 'success' : 'default'">
                                        {{ $salaryStatusOptions[$salaryComponentItem->status] ?? ucfirst($salaryComponentItem->status) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex items-center gap-1">
                                        <x-ui.button wire:click="editSalaryComponent({{ $salaryComponentItem->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                                        @if ($salaryComponentItem->status === 'active')
                                            <x-ui.button wire:click="removeSalaryComponent({{ $salaryComponentItem->id }})" variant="ghost" size="sm">Remove</x-ui.button>
                                        @endif
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-ui.empty-state
                                        title="No salary components"
                                        description="Select Basic Salary first, then add allowances, deductions, and tax components."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>
                </x-ui.card>

                <x-ui.card :title="$editingSalaryComponentId ? 'Edit Salary Component' : 'Add Salary Component'">
                    <form wire:submit="saveSalaryComponent" class="space-y-4">
                        <x-ui.select
                            wire:model="salaryComponentForm.pay_level_id"
                            label="Pay Level"
                            :options="$payLevelOptions"
                            :error="$errors->first('salaryComponentForm.pay_level_id')"
                        />

                        <x-ui.select
                            wire:model="salaryComponentForm.salary_structure_status"
                            label="Salary Status"
                            :options="$salaryStatusOptions"
                            :error="$errors->first('salaryComponentForm.salary_structure_status')"
                            required
                        />

                        <x-ui.select
                            wire:model.live="salaryComponentForm.salary_component_id"
                            label="Component"
                            :options="$salaryComponentOptions"
                            :error="$errors->first('salaryComponentForm.salary_component_id')"
                            required
                        />

                        <x-ui.select
                            wire:model.live="salaryComponentForm.calculation_type"
                            label="Calculation Type"
                            :options="$calculationTypeOptions"
                            :error="$errors->first('salaryComponentForm.calculation_type')"
                            required
                        />

                        @if ($selectedCalculationTypeIsPercentage)
                            <x-ui.input
                                wire:model="salaryComponentForm.percentage_rate"
                                type="number"
                                step="0.01"
                                label="Percentage"
                                :error="$errors->first('salaryComponentForm.percentage_rate')"
                                required
                            />

                            <x-ui.select
                                wire:model="salaryComponentForm.calculation_base"
                                label="Calculate On"
                                :options="$calculationBaseOptions"
                                :error="$errors->first('salaryComponentForm.calculation_base')"
                                required
                            />
                        @else
                            <x-ui.input
                                wire:model="salaryComponentForm.amount"
                                type="number"
                                step="0.01"
                                label="Amount"
                                :error="$errors->first('salaryComponentForm.amount')"
                                required
                            />
                        @endif

                        @if ($selectedSalaryComponentIsBasic)
                            <x-ui.input
                                wire:model="salaryComponentForm.grade_pay"
                                type="number"
                                step="0.01"
                                label="Grade Pay"
                                :error="$errors->first('salaryComponentForm.grade_pay')"
                            />
                        @endif

                        <x-ui.textarea
                            wire:model="salaryComponentForm.formula"
                            label="Formula"
                            rows="3"
                            placeholder="Optional formula or notes"
                            :error="$errors->first('salaryComponentForm.formula')"
                        />

                        <x-ui.select
                            wire:model="salaryComponentForm.status"
                            label="Component Status"
                            :options="$salaryStatusOptions"
                            :error="$errors->first('salaryComponentForm.status')"
                            required
                        />

                        <div class="flex items-center justify-end gap-2">
                            @if ($editingSalaryComponentId)
                                <x-ui.button wire:click="resetSalaryComponentForm" variant="outline">Cancel</x-ui.button>
                            @endif

                            <x-ui.button type="submit" variant="primary">
                                {{ $editingSalaryComponentId ? 'Update Component' : 'Add Component' }}
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            </div>

            <x-ui.card title="Payroll Preview">
                <div class="grid gap-5 xl:grid-cols-3">
                    <div class="xl:col-span-2">
                        <div class="grid gap-5 lg:grid-cols-2">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Earnings</h3>
                                <div class="mt-3 divide-y divide-slate-100 border border-slate-200 bg-white">
                                    @forelse ($payrollPreview['earnings'] as $earning)
                                        <div class="flex items-start justify-between gap-4 px-4 py-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $earning['name'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $earning['code'] }} · {{ $earning['detail'] }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900">{{ number_format($earning['amount'], 2) }}</p>
                                        </div>
                                    @empty
                                        <div class="px-4 py-6">
                                            <x-ui.empty-state
                                                title="No earnings"
                                                description="Add Basic Salary or earning components to preview payroll."
                                            />
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">Deductions</h3>
                                <div class="mt-3 divide-y divide-slate-100 border border-slate-200 bg-white">
                                    @forelse ($payrollPreview['deductions'] as $deduction)
                                        <div class="flex items-start justify-between gap-4 px-4 py-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $deduction['name'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $deduction['code'] }} · {{ $deduction['detail'] }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-red-700">{{ number_format($deduction['amount'], 2) }}</p>
                                        </div>
                                    @empty
                                        <div class="px-4 py-6">
                                            <x-ui.empty-state
                                                title="No deductions"
                                                description="Deduction components will appear here."
                                            />
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 bg-slate-50 p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Gross Earnings</span>
                                <span class="font-semibold text-slate-900">{{ number_format($payrollPreview['gross'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">Total Deductions</span>
                                <span class="font-semibold text-red-700">{{ number_format($payrollPreview['deductions_total'], 2) }}</span>
                            </div>
                            <div class="border-t border-slate-200 pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-700">Net Pay</span>
                                    <span class="text-lg font-bold text-green-700">{{ number_format($payrollPreview['net'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @elseif ($activeTab === 'qualifications')
        <div class="mt-5 space-y-5">
            @if (session('qualification_status'))
                <x-ui.alert variant="success">{{ session('qualification_status') }}</x-ui.alert>
            @endif

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <x-ui.table :headers="['Qualification', 'Institution', 'Year', 'Result', 'Status', '']">
                    @forelse ($employee->qualifications->sortByDesc('year_of_passing') as $qualification)
                        <tr class="transition hover:bg-slate-50">
                            <x-ui.table.td>
                                <span class="font-medium text-slate-900">{{ $qualification->qualification }}</span>
                                @if ($qualification->specialization)
                                    <p class="text-xs text-slate-400">{{ $qualification->specialization }}</p>
                                @endif
                            </x-ui.table.td>
                            <x-ui.table.td>
                                <span>{{ $qualification->institution ?: '-' }}</span>
                                @if ($qualification->board_or_university)
                                    <p class="text-xs text-slate-400">{{ $qualification->board_or_university }}</p>
                                @endif
                            </x-ui.table.td>
                            <x-ui.table.td>{{ $qualification->year_of_passing ?: '-' }}</x-ui.table.td>
                            <x-ui.table.td>{{ $qualification->percentage_or_cgpa ?: '-' }}</x-ui.table.td>
                            <x-ui.table.td>
                                <x-ui.badge :variant="$qualification->status === 'active' ? 'success' : 'default'">
                                    {{ $qualificationStatusOptions[$qualification->status] ?? ucfirst($qualification->status) }}
                                </x-ui.badge>
                            </x-ui.table.td>
                            <x-ui.table.td>
                                <div class="flex items-center gap-1">
                                    <x-ui.button wire:click="editQualification({{ $qualification->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                                    @if ($qualification->status === 'active')
                                        <x-ui.button wire:click="removeQualification({{ $qualification->id }})" variant="ghost" size="sm">Remove</x-ui.button>
                                    @endif
                                </div>
                            </x-ui.table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-ui.empty-state
                                    title="No qualifications added"
                                    description="Add academic and professional qualification details."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>

                <x-ui.card :title="$editingQualificationId ? 'Edit Qualification' : 'Add Qualification'">
                    <form wire:submit="saveQualification" class="space-y-4">
                        <x-ui.input
                            wire:model="qualificationForm.qualification"
                            label="Qualification"
                            placeholder="B.Tech, MBA, Diploma"
                            :error="$errors->first('qualificationForm.qualification')"
                            required
                        />

                        <x-ui.input
                            wire:model="qualificationForm.specialization"
                            label="Specialization"
                            placeholder="Civil Engineering, Finance"
                            :error="$errors->first('qualificationForm.specialization')"
                        />

                        <x-ui.input
                            wire:model="qualificationForm.institution"
                            label="Institution"
                            :error="$errors->first('qualificationForm.institution')"
                        />

                        <x-ui.input
                            wire:model="qualificationForm.board_or_university"
                            label="Board or University"
                            :error="$errors->first('qualificationForm.board_or_university')"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input
                                wire:model="qualificationForm.year_of_passing"
                                type="number"
                                label="Passing Year"
                                :error="$errors->first('qualificationForm.year_of_passing')"
                            />

                            <x-ui.input
                                wire:model="qualificationForm.percentage_or_cgpa"
                                label="Percentage or CGPA"
                                placeholder="72% or 8.1 CGPA"
                                :error="$errors->first('qualificationForm.percentage_or_cgpa')"
                            />
                        </div>

                        <x-ui.select
                            wire:model="qualificationForm.status"
                            label="Status"
                            :options="$qualificationStatusOptions"
                            :error="$errors->first('qualificationForm.status')"
                            required
                        />

                        <div class="flex items-center justify-end gap-2">
                            @if ($editingQualificationId)
                                <x-ui.button wire:click="resetQualificationForm" variant="outline">Cancel</x-ui.button>
                            @endif

                            <x-ui.button type="submit" variant="primary">
                                {{ $editingQualificationId ? 'Update Qualification' : 'Add Qualification' }}
                            </x-ui.button>
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
