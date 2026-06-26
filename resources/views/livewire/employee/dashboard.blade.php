<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-ui.page-header
            title="Employee Dashboard"
            description="Your HRMS overview."
        />

        <div class="flex flex-wrap gap-2">
            <x-ui.button :href="route('employee.attendance.index')">Attendance & Leave</x-ui.button>
            <x-ui.button variant="secondary" :href="route('employee.documents.index')">Documents</x-ui.button>
        </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            label="Paid Leave Left"
            :value="number_format($paidLeave['remaining'], 2)"
            hint="2 days per month"
            variant="success"
        />
        <x-ui.stat-card
            label="Pending Leave"
            :value="$leaveSummary['pending']"
            hint="Submitted or under review"
            variant="warning"
        />
        <x-ui.stat-card
            label="Documents"
            :value="$documentSummary['total']"
            hint="{{ $documentSummary['verified'] }} verified"
            variant="info"
        />
        <x-ui.stat-card
            label="Latest Payslip"
            :value="$latestPayslip?->payrollItem?->payrollBatch?->period_to?->format('M Y') ?? '-'"
            hint="Issued payslip"
        />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-5">
            <x-ui.card title="Profile Snapshot">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Employee</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->full_name }}</p>
                        <p class="text-xs text-slate-500">{{ $employee->employee_code }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Office</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->orgUnit?->name ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $employee->departmentStream?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Designation</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->designation?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Employment</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->employmentType?->name ?? '-' }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Leave Requests">
                @if ($recentLeaves->isEmpty())
                    <x-ui.empty-state title="No leave requests" description="Leave requests will appear here after you submit them." />
                @else
                    <x-ui.table :headers="['Leave Type', 'Period', 'Days', 'Status']">
                        @foreach ($recentLeaves as $leave)
                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>{{ $leave->leaveType?->name ?? '-' }}</x-ui.table.td>
                                <x-ui.table.td>
                                    {{ $leave->start_date?->format('d M Y') }} to {{ $leave->end_date?->format('d M Y') }}
                                </x-ui.table.td>
                                <x-ui.table.td>{{ number_format((float) $leave->total_days, 2) }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :variant="$leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning')">
                                        {{ str_replace('_', ' ', ucfirst($leave->status)) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>

        <aside class="space-y-5">
            <x-ui.card title="Leave Bank">
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-sm text-slate-500">Monthly paid leave allowance</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($paidLeave['allowance'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-sm text-slate-500">Used or pending</span>
                        <span class="text-sm font-semibold text-slate-900">{{ number_format($paidLeave['used'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Available</span>
                        <span class="text-sm font-semibold text-green-700">{{ number_format($paidLeave['remaining'], 2) }}</span>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Latest Payslip">
                @if ($latestPayslip)
                    <p class="font-mono text-sm font-semibold text-slate-900">{{ $latestPayslip->payslip_number }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $latestPayslip->payrollItem?->payrollBatch?->period_from?->format('d M Y') }}
                        to
                        {{ $latestPayslip->payrollItem?->payrollBatch?->period_to?->format('d M Y') }}
                    </p>
                    <p class="mt-2 text-sm text-slate-600">
                        Net payable:
                        <span class="font-semibold text-slate-900">₹{{ number_format((float) $latestPayslip->payrollItem?->net_salary, 2) }}</span>
                    </p>
                    <x-ui.button class="mt-4 w-full" :href="route('employee.payslips.index')">View Payslips</x-ui.button>
                @else
                    <p class="text-sm text-slate-500">No payslip has been issued yet.</p>
                @endif
            </x-ui.card>
        </aside>
    </div>
</section>
