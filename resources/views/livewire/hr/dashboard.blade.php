<div>
    <x-ui.page-header title="Dashboard" description="Welcome back. Here's what's happening today.">
    </x-ui.page-header>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat-card label="Total Employees" value="248" hint="Across all divisions" />
        <x-ui.stat-card label="On Leave Today" value="12" hint="4 pending approval" />
        <x-ui.stat-card label="Attendance Today" value="91%" hint="226 of 248 present" />
        <x-ui.stat-card label="Open Grievances" value="3" hint="1 escalated" />
    </div>

    {{-- Recent leave requests --}}
    <div class="mt-6">
        <x-ui.card title="Recent Leave Requests" description="Latest leave applications pending your action">
            <x-ui.table :headers="['Employee', 'Leave Type', 'Duration', 'Applied On', 'Status', '']" :padding="false">
                <tr>
                    <x-ui.table.td>
                        <span class="font-medium text-slate-900">Rahul Sharma</span>
                        <p class="text-xs text-slate-400">EMP-001</p>
                    </x-ui.table.td>
                    <x-ui.table.td>Casual Leave</x-ui.table.td>
                    <x-ui.table.td>Jun 1 – Jun 3 <span class="text-slate-400">(3 days)</span></x-ui.table.td>
                    <x-ui.table.td muted>May 26, 2026</x-ui.table.td>
                    <x-ui.table.td><x-ui.badge variant="warning">Pending</x-ui.badge></x-ui.table.td>
                    <x-ui.table.td>
                        <x-ui.button variant="ghost" size="sm">Review</x-ui.button>
                    </x-ui.table.td>
                </tr>
                <tr>
                    <x-ui.table.td>
                        <span class="font-medium text-slate-900">Priya Nath</span>
                        <p class="text-xs text-slate-400">EMP-002</p>
                    </x-ui.table.td>
                    <x-ui.table.td>Medical Leave</x-ui.table.td>
                    <x-ui.table.td>May 28 – May 30 <span class="text-slate-400">(3 days)</span></x-ui.table.td>
                    <x-ui.table.td muted>May 25, 2026</x-ui.table.td>
                    <x-ui.table.td><x-ui.badge variant="success">Approved</x-ui.badge></x-ui.table.td>
                    <x-ui.table.td>
                        <x-ui.button variant="ghost" size="sm">View</x-ui.button>
                    </x-ui.table.td>
                </tr>
            </x-ui.table>
        </x-ui.card>
    </div>

    {{-- Bottom two cols --}}
    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Upcoming events --}}
        <x-ui.card title="Upcoming" description="Holidays and events this month">
            <div class="space-y-3">
                @foreach ([['May 29', 'Eid Al-Adha', 'success', 'Holiday'], ['Jun 15', 'Payroll Processing', 'info', 'Reminder'], ['Jun 21', 'Mid-Year Review', 'info', 'Reminder']] as [$date, $label, $variant, $badgeLabel])
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 flex-col items-center justify-center border border-slate-100 bg-slate-50 text-center">
                            <span class="text-xs font-semibold text-slate-700">{{ explode(' ', $date)[1] }}</span>
                            <span class="text-xs text-slate-400">{{ explode(' ', $date)[0] }}</span>
                        </div>
                        <span class="text-sm text-slate-700">{{ $label }}</span>
                        <x-ui.badge variant="{{ $variant }}" class="ml-auto">{{ $badgeLabel }}</x-ui.badge>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Quick actions --}}
        <x-ui.card title="Quick actions">
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('hr.employees.index') }}" class="flex items-center gap-2 border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    Add Employee
                </a>
                <a href="{{ route('hr.leave.index') }}" class="flex items-center gap-2 border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Manage Leave
                </a>
                <a href="{{ route('hr.payroll.index') }}" class="flex items-center gap-2 border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Run Payroll
                </a>
                <a href="{{ route('hr.reports.index') }}" class="flex items-center gap-2 border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    View Reports
                </a>
            </div>
        </x-ui.card>
    </div>
</div>
