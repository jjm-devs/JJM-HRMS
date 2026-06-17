<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Attendance & Leave"
        description="Calendar visibility, manual leave records, and employee leave request tracking."
    />

    <div class="mt-5 border-b border-slate-200">
        <nav class="flex flex-wrap gap-1">
            @foreach ($tabs as $tab => $label)
                <button
                    type="button"
                    wire:click="setActiveTab('{{ $tab }}')"
                    class="border-b-2 px-4 py-2 text-sm font-medium transition {{ $activeTab === $tab ? 'border-blue-700 text-blue-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($activeTab === 'calendar')
        <div class="mt-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.stat-card
                    label="Active Employees"
                    :value="number_format($summary['active_employees'])"
                    hint="Present by default"
                />
                <x-ui.stat-card
                    label="Working Days"
                    :value="number_format($summary['working_days'])"
                    hint="{{ $calendarStart->format('F Y') }}"
                    variant="success"
                />
                <x-ui.stat-card
                    label="Holidays"
                    :value="number_format($summary['holidays'])"
                    hint="Active holiday records"
                    variant="warning"
                />
                <x-ui.stat-card
                    label="Leave Entries"
                    :value="number_format($summary['leave_entries'])"
                    hint="Approved leave dates"
                    variant="info"
                />
                <x-ui.stat-card
                    label="Employees On Leave"
                    :value="number_format($summary['employees_on_leave'])"
                    hint="Unique employees this month"
                    variant="danger"
                />
            </div>

            <x-ui.card>
                <div class="grid gap-3 md:grid-cols-[16rem_minmax(0,1fr)] md:items-end">
                    <x-ui.input
                        wire:model.live="month"
                        type="month"
                        label="Month"
                    />

                    <div class="flex flex-wrap gap-2 text-xs font-medium text-slate-600 md:justify-end">
                        <span class="border border-slate-200 bg-white px-2 py-1">Present</span>
                        <span class="border border-slate-300 bg-slate-100 px-2 py-1 text-slate-600">Non-working Sat</span>
                        <span class="border border-amber-200 bg-amber-50 px-2 py-1 text-amber-700">Holiday</span>
                        <span class="border border-blue-200 bg-blue-50 px-2 py-1 text-blue-700">Leave</span>
                        <span class="border border-purple-200 bg-purple-50 px-2 py-1 text-purple-700">Holiday + Leave</span>
                    </div>
                </div>
            </x-ui.card>

            <div class="overflow-hidden border border-slate-200 bg-white">
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                    @foreach ($weekdays as $weekday)
                        <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach ($calendarDays as $day)
                        @php
                            $hasHoliday = $day['holidays']->isNotEmpty();
                            $hasLeave = $day['leave_days']->isNotEmpty();
                            $isNonWorkingSat = $day['is_non_working_saturday'] ?? false;

                            $cellClass = match(true) {
                                !$day['date']                 => 'min-h-32 border-b border-r border-slate-200 p-2 bg-slate-50',
                                $hasHoliday && $hasLeave      => 'min-h-32 border-b border-r border-slate-200 p-2 bg-purple-50',
                                $hasHoliday                   => 'min-h-32 border-b border-r border-slate-200 p-2 bg-amber-50',
                                $isNonWorkingSat && $hasLeave => 'min-h-32 border-b border-r border-slate-200 p-2 bg-blue-50',
                                $isNonWorkingSat              => 'min-h-32 border-b border-r border-slate-200 p-2 bg-slate-100',
                                $hasLeave                     => 'min-h-32 border-b border-r border-slate-200 p-2 bg-blue-50',
                                default                       => 'min-h-32 border-b border-r border-slate-200 p-2 bg-white',
                            };
                        @endphp

                        <div class="{{ $cellClass }}">
                            @if ($day['date'])
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-sm font-semibold {{ $isNonWorkingSat ? 'text-slate-400' : 'text-slate-900' }}">
                                        {{ $day['date']->day }}
                                    </span>

                                    <div class="flex items-center gap-1">
                                        @if ($isNonWorkingSat)
                                            <span class="text-xs font-medium text-slate-400">Off</span>
                                        @endif
                                        @if ($hasLeave)
                                            <span class="text-xs font-semibold text-blue-700">{{ $day['leave_employee_count'] }} leave</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-2 space-y-1">
                                    @if ($isNonWorkingSat && ! $hasHoliday)
                                        <div class="border border-slate-200 bg-white px-2 py-1 text-xs text-slate-500">
                                            Non-working Saturday
                                        </div>
                                    @endif

                                    @foreach ($day['holidays'] as $holiday)
                                        <div class="border border-amber-200 bg-white px-2 py-1 text-xs text-amber-800">
                                            <span class="font-medium">{{ $holiday->name }}</span>
                                            <span class="text-amber-600">({{ ucfirst($holiday->type) }})</span>
                                        </div>
                                    @endforeach

                                    @foreach ($day['leave_days']->take(3) as $leaveDay)
                                        <div class="border border-blue-200 bg-white px-2 py-1 text-xs text-blue-800">
                                            <span class="font-medium">{{ $leaveDay->leaveApplication?->employee?->full_name ?? '-' }}</span>
                                            <span class="text-blue-600">- {{ $leaveDay->leaveApplication?->leaveType?->name ?? 'Leave' }}</span>
                                        </div>
                                    @endforeach

                                    @if ($day['leave_days']->count() > 3)
                                        <div class="border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-500">
                                            +{{ $day['leave_days']->count() - 3 }} more
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif ($activeTab === 'leave_register')
        <div class="mt-5 space-y-5">
            @if (session('leave_status'))
                <x-ui.alert variant="success">{{ session('leave_status') }}</x-ui.alert>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    label="Active Employees"
                    :value="number_format($leaveSummary['active_employees'])"
                    hint="Default present unless leave is recorded"
                />
                <x-ui.stat-card
                    label="Default Present"
                    :value="number_format($leaveSummary['default_present_employees'])"
                    hint="Employees with no approved leave in this period"
                    variant="success"
                />
                <x-ui.stat-card
                    label="On Leave"
                    :value="number_format($leaveSummary['employees_on_leave'])"
                    hint="{{ $registerFrom->format('d M Y') }} to {{ $registerTo->format('d M Y') }}"
                    variant="warning"
                />
                <x-ui.stat-card
                    label="Leave Days"
                    :value="number_format($leaveSummary['leave_days'], 2)"
                    hint="Approved leave days in selected period"
                    variant="danger"
                />
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-4">
                    <x-ui.card>
                        <div class="grid gap-3 md:grid-cols-4">
                            <x-ui.input
                                wire:model.live="dateFrom"
                                type="date"
                                label="From"
                            />

                            <x-ui.input
                                wire:model.live="dateTo"
                                type="date"
                                label="To"
                            />

                            <x-ui.input
                                wire:model.live.debounce.300ms="search"
                                label="Search Employee"
                                placeholder="Name or employee code"
                            />

                            <x-ui.select
                                wire:model.live="filterLeaveType"
                                label="Leave Type"
                                :options="$leaveTypeOptions"
                                placeholder="All leave types"
                            />
                        </div>
                    </x-ui.card>

                    <x-ui.table :headers="['Employee', 'Leave Type', 'Period', 'Days', 'Reason', 'Status', '']">
                        @forelse ($leaveRecords as $leave)
                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>
                                    <span class="font-medium text-slate-900">{{ $leave->employee?->full_name ?? '-' }}</span>
                                    <p class="text-xs text-slate-400">{{ $leave->employee?->employee_code ?? '-' }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $leave->source === 'employee_request' ? 'Employee Request' : 'Manual HR' }}
                                    </p>
                                    @if ($leave->employee?->designation || $leave->employee?->departmentStream)
                                        <p class="text-xs text-slate-400">
                                            {{ collect([$leave->employee?->designation?->name, $leave->employee?->departmentStream?->name])->filter()->implode(' · ') }}
                                        </p>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>{{ $leave->leaveType?->name ?? '-' }}</x-ui.table.td>
                                <x-ui.table.td>
                                    {{ $leave->start_date?->format('d M Y') ?? '-' }}
                                    <span class="text-slate-400">to</span>
                                    {{ $leave->end_date?->format('d M Y') ?? '-' }}
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span class="font-medium text-slate-900">{{ number_format((float) $leave->total_days, 2) }}</span>
                                    @if ($leave->month_days !== (int) $leave->total_days)
                                        <p class="text-xs text-slate-400">{{ number_format($leave->month_days, 2) }} in period</p>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span class="block max-w-xs truncate text-sm text-slate-600">{{ $leave->reason ?: '-' }}</span>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :variant="$leave->status === 'approved' ? 'success' : 'default'">
                                        {{ $leaveStatusOptions[$leave->status] ?? ucfirst($leave->status) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex items-center gap-1">
                                        <x-ui.button wire:click="editLeaveRecord({{ $leave->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                                        @if ($leave->status === 'approved')
                                            <x-ui.button wire:click="cancelLeaveRecord({{ $leave->id }})" variant="ghost" size="sm">Cancel</x-ui.button>
                                        @endif
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-ui.empty-state
                                        title="No leave recorded"
                                        description="All active employees are treated as present for the selected period."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </x-ui.table>
                </div>

                <x-ui.card :title="$editingLeaveId ? 'Edit Leave Record' : 'Record Leave'">
                    <form wire:submit="saveLeaveRecord" class="space-y-4">
                        <x-ui.select
                            wire:model="leaveForm.employee_id"
                            label="Employee"
                            :options="$employeeOptions"
                            :error="$errors->first('leaveForm.employee_id')"
                            required
                        />

                        <x-ui.select
                            wire:model="leaveForm.leave_type_id"
                            label="Leave Type"
                            :options="$leaveTypeOptions"
                            :error="$errors->first('leaveForm.leave_type_id')"
                            required
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input
                                wire:model="leaveForm.start_date"
                                type="date"
                                label="From"
                                :error="$errors->first('leaveForm.start_date')"
                                required
                            />

                            <x-ui.input
                                wire:model="leaveForm.end_date"
                                type="date"
                                label="To"
                                :error="$errors->first('leaveForm.end_date')"
                                required
                            />
                        </div>

                        <x-ui.textarea
                            wire:model="leaveForm.reason"
                            label="Reason"
                            rows="3"
                            :error="$errors->first('leaveForm.reason')"
                        />

                        <x-ui.input
                            wire:model="leaveForm.contact_during_leave"
                            label="Contact During Leave"
                            :error="$errors->first('leaveForm.contact_during_leave')"
                        />

                        <x-ui.select
                            wire:model="leaveForm.status"
                            label="Status"
                            :options="$leaveStatusOptions"
                            :error="$errors->first('leaveForm.status')"
                            required
                        />

                        <div class="flex items-center justify-end gap-2">
                            @if ($editingLeaveId)
                                <x-ui.button wire:click="resetLeaveForm" variant="outline">Cancel</x-ui.button>
                            @endif

                            <x-ui.button type="submit" variant="primary">
                                {{ $editingLeaveId ? 'Update Leave' : 'Record Leave' }}
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>

    @elseif ($activeTab === 'leave_requests')
        <div class="mt-5 space-y-5">
            @if (session('leave_status'))
                <x-ui.alert variant="success">{{ session('leave_status') }}</x-ui.alert>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    label="Submitted"
                    :value="number_format($leaveRequestSummary['submitted'])"
                    hint="Awaiting HR action"
                    variant="warning"
                />
                <x-ui.stat-card
                    label="Under Review"
                    :value="number_format($leaveRequestSummary['under_review'])"
                    hint="Being checked"
                    variant="info"
                />
                <x-ui.stat-card
                    label="Approved"
                    :value="number_format($leaveRequestSummary['approved'])"
                    hint="Employee requests approved"
                    variant="success"
                />
                <x-ui.stat-card
                    label="Rejected"
                    :value="number_format($leaveRequestSummary['rejected'])"
                    hint="Employee requests rejected"
                    variant="danger"
                />
            </div>

            <x-ui.table :headers="['Employee', 'Leave Type', 'Period', 'Days', 'Requested On', 'Status', 'Documents', '']">
                @forelse ($employeeLeaveRequests as $request)
                    <tr class="transition hover:bg-slate-50">
                        <x-ui.table.td>
                            <span class="font-medium text-slate-900">{{ $request->employee?->full_name ?? '-' }}</span>
                            <p class="text-xs text-slate-400">{{ $request->employee?->employee_code ?? '-' }}</p>
                        </x-ui.table.td>
                        <x-ui.table.td>{{ $request->leaveType?->name ?? '-' }}</x-ui.table.td>
                        <x-ui.table.td>
                            {{ $request->start_date?->format('d M Y') ?? '-' }}
                            <span class="text-slate-400">to</span>
                            {{ $request->end_date?->format('d M Y') ?? '-' }}
                        </x-ui.table.td>
                        <x-ui.table.td>
                            {{ number_format((float) $request->total_days, 2) }}
                        </x-ui.table.td>
                        <x-ui.table.td>{{ $request->created_at?->format('d M Y') ?? '-' }}</x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :variant="$request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'danger' : 'warning')">
                                {{ str_replace('_', ' ', ucfirst($request->status)) }}
                            </x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            @if ($request->documents->isEmpty())
                                <span class="text-xs text-slate-400">No documents</span>
                            @else
                                <div class="space-y-1">
                                    @foreach ($request->documents as $document)
                                        <button
                                            type="button"
                                            wire:click="downloadLeaveRequestDocument({{ $document->id }})"
                                            class="block text-left text-xs font-medium text-blue-700 hover:underline"
                                        >
                                            {{ $document->title }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <div class="flex flex-wrap items-center gap-1">
                                @if (in_array($request->status, ['submitted', 'under_review'], true))
                                    <x-ui.button wire:click="approveLeaveRequest({{ $request->id }})" variant="ghost" size="sm">Approve</x-ui.button>
                                    <x-ui.button wire:click="openApproveLeaveRequestModal({{ $request->id }})" variant="ghost" size="sm">Upload & Approve</x-ui.button>
                                @endif
                                <x-ui.button wire:click="printLeaveApplication({{ $request->id }})" variant="ghost" size="sm">Print</x-ui.button>
                            </div>
                        </x-ui.table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-ui.empty-state
                                title="No employee leave requests"
                                description="Employee-submitted requests will appear here."
                            />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
        </div>
    @endif

    <x-ui.modal name="approve-leave-request" title="Approve Leave Request" size="lg">
        <div class="space-y-4">
            <p class="text-sm text-slate-600">
                Approve the employee request. You may attach the physically signed application now; the employee will be able to download it from their leave details.
            </p>

            <x-ui.textarea
                wire:model="leaveApprovalRemarks"
                label="Approval Remarks"
                placeholder="Optional remarks"
                :error="$errors->first('leaveApprovalRemarks')"
            />

            <div>
                <label class="text-sm font-medium text-slate-800">Signed Application</label>
                <input
                    type="file"
                    wire:model="signedLeaveDocumentFile"
                    class="mt-1 block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                />
                <p class="mt-1 text-xs text-slate-500">PDF, JPG, JPEG, PNG, DOC, or DOCX up to 10 MB.</p>
                @error('signedLeaveDocumentFile')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'approve-leave-request' })">Cancel</x-ui.button>
            <x-ui.button wire:click="approveSelectedLeaveRequest" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="approveSelectedLeaveRequest">Approve</span>
                <span wire:loading wire:target="approveSelectedLeaveRequest">Approving...</span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</section>
