<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="Employees" description="Manage employee profiles, postings, service status, and HR records.">
        <x-ui.button :href="route('hr.employees.create')" variant="primary">
            Add Employee
        </x-ui.button>
    </x-ui.page-header>

    <x-ui.card class="mb-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                label="Search"
                placeholder="Name, code, or PAN"
            />

            <x-ui.select
                wire:model.live="filterOrgUnit"
                label="Office / Unit"
                :options="$orgUnits->pluck('name', 'id')->all()"
                placeholder="All units"
            />

            <x-ui.select
                wire:model.live="filterDepartmentStream"
                label="Stream"
                :options="$departmentStreams->pluck('name', 'id')->all()"
                placeholder="All streams"
            />

            <x-ui.select
                wire:model.live="filterEmploymentType"
                label="Employment"
                :options="$employmentTypes->pluck('name', 'id')->all()"
                placeholder="All types"
            />

            <x-ui.select
                wire:model.live="filterStatus"
                label="Status"
                :options="[
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'on_leave' => 'On Leave',
                    'retired' => 'Retired',
                    'suspended' => 'Suspended',
                ]"
                placeholder="All statuses"
            />
        </div>
    </x-ui.card>

    <x-ui.table :headers="['Employee', 'Designation', 'Office / Unit', 'Stream', 'Employment', 'Status', '']">
        @forelse ($employees as $employee)
            <tr class="transition hover:bg-slate-50">
                <x-ui.table.td>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                            {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-medium text-slate-900">{{ $employee->full_name }}</p>
                            <p class="text-xs text-slate-400">{{ $employee->employee_code }}</p>
                        </div>
                    </div>
                </x-ui.table.td>
                <x-ui.table.td>{{ $employee->designation?->name ?? '-' }}</x-ui.table.td>
                <x-ui.table.td>{{ $employee->orgUnit?->name ?? '-' }}</x-ui.table.td>
                <x-ui.table.td>{{ $employee->departmentStream?->name ?? '-' }}</x-ui.table.td>
                <x-ui.table.td>{{ $employee->employmentType?->name ?? '-' }}</x-ui.table.td>
                <x-ui.table.td>
                    @php
                        $statusMap = [
                            'active' => ['variant' => 'success', 'label' => 'Active'],
                            'inactive' => ['variant' => 'default', 'label' => 'Inactive'],
                            'on_leave' => ['variant' => 'warning', 'label' => 'On Leave'],
                            'retired' => ['variant' => 'default', 'label' => 'Retired'],
                            'suspended' => ['variant' => 'danger', 'label' => 'Suspended'],
                        ];
                        $status = $statusMap[$employee->service_status] ?? ['variant' => 'default', 'label' => $employee->service_status];
                    @endphp
                    <x-ui.badge :variant="$status['variant']">{{ $status['label'] }}</x-ui.badge>
                </x-ui.table.td>
                <x-ui.table.td>
                    <div class="flex items-center gap-1">
                        <x-ui.button :href="route('hr.employees.show', $employee)" variant="ghost" size="sm">View</x-ui.button>
                        <x-ui.button :href="route('hr.employees.edit', $employee)" variant="ghost" size="sm">Edit</x-ui.button>
                    </div>
                </x-ui.table.td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-ui.empty-state
                        title="No employees found"
                        description="Try changing the filters, or add the first employee profile."
                    >
                        <x-ui.button :href="route('hr.employees.create')" variant="primary" size="sm">Add Employee</x-ui.button>
                    </x-ui.empty-state>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if ($employees->hasPages())
        <div class="mt-4">
            {{ $employees->links() }}
        </div>
    @endif
</section>
