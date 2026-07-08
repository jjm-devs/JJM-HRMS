<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="Reports" description="Download HR data as CSV files (opens in Excel)." />

    <x-ui.card title="Filters" class="mb-6">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.input wire:model="dateFrom" type="date" label="From date" />
            <x-ui.input wire:model="dateTo" type="date" label="To date" />
            <x-ui.select
                wire:model="filterOrgUnit"
                label="Office / Unit"
                :options="$orgUnits->pluck('name', 'id')->all()"
                placeholder="All offices"
            />
            <x-ui.select
                wire:model="filterDepartmentStream"
                label="Stream"
                :options="$departmentStreams->pluck('name', 'id')->all()"
                placeholder="All streams"
            />
        </div>
        <p class="mt-3 text-xs text-slate-400">
            Dates apply to the Payroll, Leave and Transfer reports. Office / Stream apply to the Employee Roster.
        </p>
    </x-ui.card>

    <div class="grid gap-4 md:grid-cols-2">
        @php
            $reports = [
                ['Employee Roster', 'Every staff member you manage — office, stream, designation, bank details.', 'downloadRoster'],
                ['Payroll Summary', 'Salary lines (gross, deductions, net) for the selected date range.', 'downloadPayroll'],
                ['Leave Summary', 'Leave applications that start within the selected date range.', 'downloadLeave'],
                ['Transfer Register', 'Staff transfers recorded within the selected date range.', 'downloadTransfers'],
            ];
        @endphp

        @foreach ($reports as [$title, $desc, $action])
            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $desc }}</p>
                    </div>
                    <x-ui.button wire:click="{{ $action }}" variant="primary" class="shrink-0">
                        Download CSV
                    </x-ui.button>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</section>
