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

            $bankRows = [
                'Account Number' => $employee->bank_account_number,
                'IFSC Code' => $employee->bank_ifsc_code,
                'Bank Name' => $employee->bank_name,
                'Branch' => $employee->bank_branch,
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
            @include('livewire.shared.employee-contacts')
        @elseif ($activeTab === 'family')
            @include('livewire.shared.employee-family-members')
        @elseif ($activeTab === 'bank')
            <div class="mt-5">
                <x-ui.card title="Bank Account Details">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        @foreach ($bankRows as $label => $value)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-ui.card>
            </div>
        @else
            <div class="mt-5">
                <x-ui.card title="Documents">
                    <x-ui.empty-state title="Document upload will be added next" description="This is where Aadhaar, PAN, certificates, and appointment documents will go." />
                </x-ui.card>
            </div>
        @endif
    @endif
</section>
