<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-ui.page-header
            title="Attendance & Leave"
            description="Check in, check out, view your calendar, and apply for leave."
        />

        <x-ui.button wire:click="openApplyModal">
            Apply Leave
        </x-ui.button>
    </div>

    @if (session('attendance_status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('attendance_status') }}</x-ui.alert>
    @endif

    @if (session('attendance_error'))
        <x-ui.alert variant="danger" class="mt-5">{{ session('attendance_error') }}</x-ui.alert>
    @endif

    @if (session('leave_status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('leave_status') }}</x-ui.alert>
    @endif

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Paid Leave Left"
            :value="number_format($paidLeaveBank['remaining'], 2)"
            hint="2 days per month"
            variant="success"
        />
        <x-ui.stat-card
            label="Paid Leave Used"
            :value="number_format($paidLeaveBank['used'], 2)"
            hint="{{ $paidLeaveBank['leave_type']?->name ?? 'Paid Leave' }}"
            variant="warning"
        />
        <x-ui.stat-card
            label="Monthly Bank"
            :value="number_format($paidLeaveBank['allowance'], 2)"
            hint="{{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $month)->format('F Y') }}"
            variant="info"
        />
        <x-ui.stat-card
            label="Leave Requests"
            :value="$leaveHistory->count()"
            hint="Latest records shown below"
        />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-5">
            <x-ui.card>
                <div class="grid gap-3 md:grid-cols-[16rem_minmax(0,1fr)] md:items-end">
                    <x-ui.input
                        wire:model.live="month"
                        type="month"
                        label="Month"
                    />

                    <div class="flex flex-wrap gap-2 text-xs font-medium text-slate-600 md:justify-end">
                        <span class="border border-slate-200 bg-white px-2 py-1">Working day</span>
                        <span class="border border-slate-300 bg-slate-100 px-2 py-1 text-slate-600">Off</span>
                        <span class="border border-blue-200 bg-blue-50 px-2 py-1 text-blue-700">Leave</span>
                        <span class="border border-green-200 bg-green-50 px-2 py-1 text-green-700">Checked in</span>
                        <span class="border border-amber-200 bg-amber-50 px-2 py-1 text-amber-700">Holiday</span>
                    </div>
                </div>
            </x-ui.card>

            <div class="overflow-hidden border border-slate-200 bg-white">
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
                    @foreach ($weekdays as $weekday)
                        <div class="px-2 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:px-3">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7">
                    @foreach ($calendarDays as $day)
                        @php
                            $hasHoliday = $day['holidays']->isNotEmpty();
                            $hasLeave = $day['leave_days']->isNotEmpty();
                            $hasPunch = $day['attendance_log'] !== null;
                            $isOff = $day['is_non_working_saturday'] || ($day['date']?->isSunday() ?? false);

                            $cellClass = match(true) {
                                ! $day['date'] => 'min-h-28 border-b border-r border-slate-200 bg-slate-50 p-2',
                                $hasLeave => 'min-h-28 border-b border-r border-slate-200 bg-blue-50 p-2 text-left transition hover:bg-blue-100',
                                $hasPunch => 'min-h-28 border-b border-r border-slate-200 bg-green-50 p-2 text-left transition hover:bg-green-100',
                                $hasHoliday => 'min-h-28 border-b border-r border-slate-200 bg-amber-50 p-2 text-left transition hover:bg-amber-100',
                                $isOff => 'min-h-28 border-b border-r border-slate-200 bg-slate-100 p-2 text-left transition hover:bg-slate-200',
                                default => 'min-h-28 border-b border-r border-slate-200 bg-white p-2 text-left transition hover:bg-slate-50',
                            };
                        @endphp

                        @if ($day['date'])
                            <button
                                type="button"
                                wire:click="applyForDate('{{ $day['date']->format('Y-m-d') }}')"
                                class="{{ $cellClass }} w-full"
                            >
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-sm font-semibold {{ $day['is_today'] ? 'text-blue-700' : 'text-slate-900' }}">
                                        {{ $day['date']->day }}
                                    </span>
                                    @if ($day['is_today'])
                                        <span class="text-xs font-medium text-blue-700">Today</span>
                                    @elseif ($isOff)
                                        <span class="text-xs font-medium text-slate-400">Off</span>
                                    @endif
                                </div>

                                <div class="mt-2 space-y-1">
                                    @foreach ($day['holidays']->take(1) as $holiday)
                                        <div class="border border-amber-200 bg-white px-2 py-1 text-xs text-amber-800">
                                            {{ $holiday->name }}
                                        </div>
                                    @endforeach

                                    @foreach ($day['leave_days']->take(2) as $leaveDay)
                                        @php
                                            $leave = $leaveDay->leaveApplication;
                                            $variant = match($leave?->status) {
                                                'approved' => 'border-blue-200 text-blue-800',
                                                'rejected' => 'border-red-200 text-red-700',
                                                default => 'border-slate-200 text-slate-700',
                                            };
                                        @endphp
                                        <div class="border bg-white px-2 py-1 text-xs {{ $variant }}">
                                            <span class="font-medium">{{ $leave?->leaveType?->name ?? 'Leave' }}</span>
                                            <span class="block text-slate-400">{{ str_replace('_', ' ', ucfirst($leave?->status ?? 'submitted')) }}</span>
                                        </div>
                                    @endforeach

                                    @if ($day['attendance_log'])
                                        <div class="border border-green-200 bg-white px-2 py-1 text-xs text-green-800">
                                            In {{ $day['attendance_log']->check_in?->format('h:i A') ?? '-' }}
                                            @if ($day['attendance_log']->check_out)
                                                <span class="block">Out {{ $day['attendance_log']->check_out->format('h:i A') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @else
                            <div class="{{ $cellClass }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <x-ui.card title="Check-in / Check-out" description="Punches are logged only when you use these actions.">
                <div class="space-y-3">
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                        @if ($todayLog)
                            <p>Check-in: <span class="font-semibold text-slate-900">{{ $todayLog->check_in?->format('h:i A') ?? '-' }}</span></p>
                            <p>Check-out: <span class="font-semibold text-slate-900">{{ $todayLog->check_out?->format('h:i A') ?? '-' }}</span></p>
                        @else
                            <p>No punch recorded yet.</p>
                        @endif
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                        <x-ui.button
                            wire:click="checkIn"
                            :disabled="$todayLog?->check_in !== null"
                        >
                            Check In
                        </x-ui.button>
                        <x-ui.button
                            variant="secondary"
                            wire:click="checkOut"
                            :disabled="! $todayLog?->check_in || $todayLog?->check_out"
                        >
                            Check Out
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Leave Requests" description="Click any calendar date to start a request for that date.">
                @if ($leaveHistory->isEmpty())
                    <p class="py-6 text-center text-sm text-slate-400">No leave requests yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($leaveHistory as $leave)
                            <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $leave->leaveType?->name ?? 'Leave' }}</span>
                                    <x-ui.badge :variant="$leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning')">
                                        {{ str_replace('_', ' ', ucfirst($leave->status)) }}
                                    </x-ui.badge>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $leave->start_date?->format('d M Y') }} to {{ $leave->end_date?->format('d M Y') }}
                                    - {{ number_format((float) $leave->total_days, 2) }} day(s)
                                </p>
                                @if ($leave->approval_remarks)
                                    <p class="mt-1 text-xs text-slate-500">{{ $leave->approval_remarks }}</p>
                                @endif
                                @if ($leave->documents->isNotEmpty())
                                    <p class="mt-1 text-xs text-slate-400">{{ $leave->documents->count() }} attachment(s)</p>
                                @endif
                                <button
                                    type="button"
                                    wire:click="openLeaveDetail({{ $leave->id }})"
                                    class="mt-2 text-xs font-semibold text-blue-700 hover:underline"
                                >
                                    View Details
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </aside>
    </div>

    <x-ui.modal name="employee-leave-request" title="Apply Leave" size="lg" :show="$showLeaveModal">
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select
                    wire:model.live="leaveForm.leave_type_id"
                    label="Leave Type"
                    :options="$leaveTypeOptions"
                    :error="$errors->first('leaveForm.leave_type_id')"
                    required
                />
                <x-ui.input
                    wire:model="leaveForm.contact_during_leave"
                    label="Contact During Leave"
                    placeholder="Mobile or alternate contact"
                    :error="$errors->first('leaveForm.contact_during_leave')"
                />
            </div>

            @if ($selectedLeaveBalance)
                <div class="border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    {{ $selectedLeaveBalance }}
                </div>
            @endif

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
                placeholder="Write your leave request message"
                rows="4"
                :error="$errors->first('leaveForm.reason')"
                required
            />

            <div>
                <label class="text-sm font-medium text-slate-800">Attachments</label>
                <input
                    type="file"
                    wire:model="attachments"
                    multiple
                    class="mt-1 block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                />
                <p class="mt-1 text-xs text-slate-500">PDF, JPG, JPEG, or PNG up to 10 MB each.</p>
                @error('attachments')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'employee-leave-request' })">Cancel</x-ui.button>
            <x-ui.button wire:click="submitLeaveRequest" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submitLeaveRequest">Submit Request</span>
                <span wire:loading wire:target="submitLeaveRequest">Submitting...</span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="employee-leave-detail" title="Leave Details" size="lg">
        @if ($selectedLeave)
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Leave Type</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $selectedLeave->leaveType?->name ?? 'Leave' }}</p>
                    </div>
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Status</p>
                        <x-ui.badge :variant="$selectedLeave->status === 'approved' ? 'success' : ($selectedLeave->status === 'rejected' ? 'danger' : 'warning')">
                            {{ str_replace('_', ' ', ucfirst($selectedLeave->status)) }}
                        </x-ui.badge>
                    </div>
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Period</p>
                        <p class="text-sm text-slate-700">
                            {{ $selectedLeave->start_date?->format('d M Y') }} to {{ $selectedLeave->end_date?->format('d M Y') }}
                        </p>
                    </div>
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Days</p>
                        <p class="text-sm font-semibold text-slate-900">{{ number_format((float) $selectedLeave->total_days, 2) }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Reason</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $selectedLeave->reason ?: '-' }}</p>
                </div>

                @if ($selectedLeave->approval_remarks || $selectedLeave->approvedBy)
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Approval</p>
                        <p class="mt-1 text-sm text-slate-700">
                            @if ($selectedLeave->approvedBy)
                                Approved by {{ $selectedLeave->approvedBy->name }}
                                @if ($selectedLeave->approved_at)
                                    on {{ $selectedLeave->approved_at->format('d M Y') }}
                                @endif
                            @else
                                Awaiting approval
                            @endif
                        </p>
                        @if ($selectedLeave->approval_remarks)
                            <p class="mt-1 text-sm text-slate-600">{{ $selectedLeave->approval_remarks }}</p>
                        @endif
                    </div>
                @endif

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Documents</p>
                    @if ($selectedLeave->documents->isEmpty())
                        <p class="mt-2 text-sm text-slate-400">No documents attached.</p>
                    @else
                        <div class="mt-2 space-y-2">
                            @foreach ($selectedLeave->documents as $document)
                                <div class="flex flex-wrap items-center justify-between gap-2 border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $document->title }}</p>
                                        <p class="text-xs text-slate-400">
                                            {{ $document->file_name }} · {{ str_replace('_', ' ', ucfirst($document->status)) }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="downloadLeaveDocument({{ $document->id }})"
                                        class="text-sm font-medium text-blue-700 hover:underline"
                                    >
                                        Download
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @else
            <p class="py-6 text-center text-sm text-slate-400">Select a leave request to view details.</p>
        @endif

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'employee-leave-detail' })">Close</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</section>
