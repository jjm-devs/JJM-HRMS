<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-ui.page-header
            title="Payslips"
            description="View and download issued payslips."
        />

        <x-ui.select
            wire:model.live="filterYear"
            label="Year"
            :options="$years"
            placeholder="All years"
        />
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Issued Payslips" :value="$summary['issued']" hint="For selected year" />
        <x-ui.stat-card label="Downloads" :value="$summary['downloads']" hint="Your download count" variant="info" />
        <x-ui.stat-card
            label="Latest"
            :value="$summary['latest']?->payrollItem?->payrollBatch?->period_to?->format('M Y') ?? '-'"
            hint="Most recent payslip"
            variant="success"
        />
    </div>

    <x-ui.card class="mt-5">
        @if ($payslips->isEmpty())
            <x-ui.empty-state
                title="No payslips issued"
                description="Payslips will appear here after HR generates them from an approved payroll batch."
            />
        @else
            <x-ui.table :headers="['Payslip', 'Period', 'Gross', 'Deductions', 'Net Payable', 'Generated', '']">
                @foreach ($payslips as $payslip)
                    @php
                        $item = $payslip->payrollItem;
                        $batch = $item?->payrollBatch;
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <x-ui.table.td>
                            <span class="font-mono text-sm font-semibold text-slate-900">{{ $payslip->payslip_number }}</span>
                            <p class="text-xs text-slate-400">{{ $batch?->batch_number ?? '-' }}</p>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            {{ $batch?->period_from?->format('d M Y') ?? '-' }}
                            <span class="text-slate-400">to</span>
                            {{ $batch?->period_to?->format('d M Y') ?? '-' }}
                        </x-ui.table.td>
                        <x-ui.table.td>₹{{ number_format((float) $item?->gross_salary, 2) }}</x-ui.table.td>
                        <x-ui.table.td>₹{{ number_format((float) $item?->total_deductions, 2) }}</x-ui.table.td>
                        <x-ui.table.td>
                            <span class="font-semibold text-slate-900">₹{{ number_format((float) $item?->net_salary, 2) }}</span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            {{ $payslip->generated_at?->format('d M Y') ?? '-' }}
                            <p class="text-xs text-slate-400">{{ $payslip->download_count }} download(s)</p>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <button
                                type="button"
                                wire:click="downloadPayslip({{ $payslip->id }})"
                                class="text-sm font-medium text-blue-700 hover:underline"
                            >
                                Download
                            </button>
                        </x-ui.table.td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
</section>
