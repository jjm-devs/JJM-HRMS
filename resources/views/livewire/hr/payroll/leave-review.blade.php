<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a
                href="{{ route('hr.payroll.batch.detail', $batch) }}"
                class="text-sm text-slate-400 hover:text-slate-700"
            >
                ← {{ $batch->batch_number }}
            </a>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Leave Review</h1>
            <p class="text-sm text-slate-500">
                {{ $item->employee?->full_name }} · {{ $item->employee?->employee_code }}
            </p>
        </div>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- salary snapshot --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Gross Salary"    :value="'₹' . number_format($item->gross_salary, 2)"      variant="info" />
        <x-ui.stat-card label="Total Deductions" :value="'₹' . number_format($item->total_deductions, 2)"  variant="danger" />
        <x-ui.stat-card label="Net Salary"       :value="'₹' . number_format($item->net_salary, 2)"        variant="success" />
    </div>

    {{-- leave adjustments table --}}
    <x-ui.card class="mt-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Leave Adjustments in Pay Period</h2>
            <x-ui.button variant="secondary" size="sm" wire:click="resetToAuto">
                Reset to Auto
            </x-ui.button>
        </div>

        @if ($item->leaveAdjustments->isEmpty())
            <p class="py-6 text-center text-sm text-slate-400">No leave records found for this pay period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2">Leave Type</th>
                            <th class="px-3 py-2">Period</th>
                            <th class="px-3 py-2">Days</th>
                            <th class="px-3 py-2">Paid Leave</th>
                            <th class="px-3 py-2">Had Balance</th>
                            <th class="px-3 py-2">Auto</th>
                            <th class="px-3 py-2">Reviewed Classification</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item->leaveAdjustments as $adj)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $adj->leave_type_name }}</td>
                                <td class="px-3 py-3 text-slate-600">
                                    {{ $adj->leaveApplication?->start_date?->format('d M') }}
                                    – {{ $adj->leaveApplication?->end_date?->format('d M Y') }}
                                </td>
                                <td class="px-3 py-3 text-slate-700">{{ $adj->leave_days }}</td>
                                <td class="px-3 py-3">
                                    @if ($adj->leave_type_is_paid)
                                        <span class="bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Paid</span>
                                    @else
                                        <span class="bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">Unpaid</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($adj->leave_type_is_paid)
                                        @if ($adj->had_sufficient_balance)
                                            <span class="text-green-600">Yes</span>
                                        @else
                                            <span class="text-red-600">No</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @php
                                        $autoClass = match($adj->auto_classification) {
                                            'salary_deduct' => 'bg-red-100 text-red-700',
                                            'leave_bank'    => 'bg-blue-100 text-blue-700',
                                            'exempt'        => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs font-semibold {{ $autoClass }}">
                                        {{ str_replace('_', ' ', ucfirst($adj->auto_classification)) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <select
                                        wire:model="classifications.{{ $adj->id }}"
                                        class="border border-slate-300 bg-white px-2 py-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="salary_deduct">Salary Deduct</option>
                                        <option value="leave_bank">Leave Bank</option>
                                        <option value="exempt">Exempt</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                <strong>Salary Deduct</strong> — deducted from this month's salary at ₹{{ number_format($item->gross_salary, 2) }} ÷ 30 per day.<br>
                <strong>Leave Bank</strong> — deducted from leave balance, no salary impact.<br>
                <strong>Exempt</strong> — no deduction of any kind (e.g. special approval).
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <a
                    href="{{ route('hr.payroll.batch.detail', $batch) }}"
                    class="border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Cancel
                </a>
                <x-ui.button wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save & Recalculate</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </x-ui.button>
            </div>
        @endif
    </x-ui.card>

</section>
