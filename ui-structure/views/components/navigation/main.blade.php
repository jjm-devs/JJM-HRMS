@php
    $user = auth()->user();

    $links = $user?->is_hr
        ? [
            ['label' => 'Dashboard', 'route' => 'hr.dashboard'],
            ['label' => 'Employees', 'route' => 'hr.employees.index'],
            ['label' => 'Attendance', 'route' => 'hr.attendance.index'],
            ['label' => 'Leave', 'route' => 'hr.leave.index'],
            ['label' => 'Payroll', 'route' => 'hr.payroll.index'],
            ['label' => 'Documents', 'route' => 'hr.documents.index'],
            ['label' => 'Transfers', 'route' => 'hr.transfers.index'],
            ['label' => 'Reports', 'route' => 'hr.reports.index'],
        ]
        : [
            ['label' => 'Dashboard', 'route' => 'employee.dashboard'],
            ['label' => 'Profile', 'route' => 'employee.profile.index'],
            ['label' => 'Attendance', 'route' => 'employee.attendance.index'],
            ['label' => 'Leave', 'route' => 'employee.leave.index'],
            ['label' => 'Documents', 'route' => 'employee.documents.index'],
            ['label' => 'Payslips', 'route' => 'employee.payslips.index'],
        ];
@endphp

<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-6 py-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">JJM Brain HRMS</p>
                <p class="mt-1 text-sm text-slate-600">{{ $user->name }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="lg:order-3">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Logout
                </button>
            </form>

            <nav class="flex flex-wrap gap-2">
                @foreach ($links as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        class="{{ request()->routeIs($link['route']) ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} rounded-lg px-3 py-2 text-sm font-medium transition"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</header>
