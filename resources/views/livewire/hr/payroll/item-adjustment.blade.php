<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('hr.payroll.batch.detail', $batch) }}" class="text-sm text-slate-400 hover:text-slate-700">
                ← {{ $batch->batch_number }}
            </a>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Manual Adjustments</h1>
            <p class="text-sm text-slate-500">
                {{ $item->employee?->full_name }}
                · {{ $item->employee?->employee_code }}
                · {{ $batch->periodLabel() }}
            </p>
        </div>
        @if ($batch->isPartial())
            <span class="inline-flex items-center bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-700">
                Partial Batch · {{ $batch->default_disbursement_pct }}% default
            </span>
        @endif
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- salary summary --}}
    <div class="mt-5 grid gap-4 {{ $batch->isPartial() ? 'sm:grid-cols-3 xl:grid-cols-5' : 'sm:grid-cols-4' }}">
        <x-ui.stat-card label="Gross Salary"
            :value="'₹' . number_format($item->gross_salary, 2)" />
        <x-ui.stat-card label="Total Deductions"
            :value="'₹' . number_format(max(0, $item->total_deductions + $adjustments->where('type', 'deduction')->sum('amount') - $adjustments->where('type', 'addition')->sum('amount')), 2)"
            variant="danger" />
        <x-ui.stat-card
            label="Adj. Net Effect"
            :value="($adjustments->where('type','addition')->sum('amount') - $adjustments->where('type','deduction')->sum('amount') >= 0 ? '+' : '') . '₹' . number_format($adjustments->where('type','addition')->sum('amount') - $adjustments->where('type','deduction')->sum('amount'), 2)"
            variant="info" />
        <x-ui.stat-card label="Net Payable"
            :value="'₹' . number_format($item->net_salary, 2)"
            variant="success" />

        @if ($batch->isPartial())
            <x-ui.stat-card
                label="To Disburse ({{ $item->disbursement_pct }}%)"
                :value="'₹' . number_format($item->disbursed_amount, 2)"
                :hint="$item->hasCustomDisbursementPct() ? 'Custom %' : 'Batch default %'"
                variant="warning" />
            <x-ui.stat-card
                label="Outstanding"
                :value="'₹' . number_format($item->outstanding_amount, 2)"
                hint="To be paid as arrear"
                variant="danger" />
        @endif
    </div>

    {{-- disbursement override — partial batches only --}}
    @if ($batch->isPartial() && $canEditPayroll)
        <x-ui.card class="mt-6">
            <h2 class="mb-1 text-sm font-semibold text-slate-700">Disbursement Override</h2>
            <p class="mb-4 text-xs text-slate-400">
                Batch default is <strong>{{ $batch->default_disbursement_pct }}%</strong>.
                Override below to give this employee a different disbursement percentage.
            </p>

            @if ($item->hasCustomDisbursementPct())
                <div class="mb-4 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
                    This employee has a custom disbursement of <strong>{{ $item->disbursement_pct }}%</strong>
                    (batch default: {{ $batch->default_disbursement_pct }}%).
                    <br>Reason: <em>{{ $item->disbursement_override_reason }}</em>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input
                    wire:model="overridePct"
                    type="number"
                    min="1"
                    max="100"
                    step="0.01"
                    label="Disbursement % for this employee"
                    hint="1–100"
                />
                <x-ui.input
                    wire:model="overrideReason"
                    label="Reason for override"
                    placeholder="e.g. Employee requested full payment"
                />
            </div>

            <div class="mt-4 flex items-center gap-3">
                <x-ui.button wire:click="saveDisbursementOverride">
                    Update Disbursement %
                </x-ui.button>
                @if ($item->hasCustomDisbursementPct())
                    <x-ui.button
                        variant="secondary"
                        wire:click="resetDisbursementOverride"
                        wire:confirm="Reset to batch default of {{ $batch->default_disbursement_pct }}%?"
                    >
                        Reset to Default
                    </x-ui.button>
                @endif
            </div>
        </x-ui.card>
    @endif

    {{-- existing adjustments --}}
    @if ($adjustments->isNotEmpty())
        <x-ui.card class="mt-6">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Saved Adjustments</h2>
            <x-ui.table :headers="['Label', 'Type', 'Amount', 'Note', 'By', 'Workflow', '']">
                @foreach ($adjustments as $adj)
                    <tr class="transition hover:bg-slate-50">
                        <x-ui.table.td>
                            <span class="font-medium text-slate-900">{{ $adj->label }}</span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            @if ($adj->type === 'addition')
                                <span class="inline-flex items-center bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Addition</span>
                            @else
                                <span class="inline-flex items-center bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Deduction</span>
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="text-sm font-medium {{ $adj->type === 'addition' ? 'text-green-700' : 'text-red-600' }}">
                                {{ $adj->type === 'addition' ? '+' : '−' }}₹{{ number_format($adj->amount, 2) }}
                            </span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="text-sm text-slate-500">{{ $adj->note ?? '—' }}</span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="text-xs text-slate-500">{{ $adj->updatedBy?->name ?? $adj->createdBy?->name ?? '—' }}</span>
                            <p class="text-xs text-slate-400">{{ $adj->created_at->format('d M Y') }}</p>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span class="text-xs text-slate-500">
                                {{ \App\Models\Role::labelFor($adj->role) }}
                            </span>
                            @if ($adj->workflowStep)
                                <p class="text-xs text-slate-400">{{ $adj->workflowStep->name }}</p>
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td>
                            @if ($canEditPayroll)
                                <div class="flex items-center gap-3">
                                    <button
                                        wire:click="edit({{ $adj->id }})"
                                        class="text-sm font-medium text-blue-700 hover:underline"
                                    >Edit</button>
                                    <button
                                        wire:click="delete({{ $adj->id }})"
                                        wire:confirm="Remove this adjustment?"
                                        class="text-sm font-medium text-red-600 hover:underline"
                                    >Remove</button>
                                </div>
                            @endif
                        </x-ui.table.td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    @endif

    {{-- add / edit adjustment form --}}
    @if ($canEditPayroll)
        <x-ui.card class="mt-6">
            <h2 class="mb-4 text-sm font-semibold text-slate-700">
                {{ $editingId ? 'Edit Adjustment' : 'Add Adjustment' }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select
                    wire:model="type"
                    label="Type"
                    :options="['addition' => 'Addition', 'deduction' => 'Deduction']"
                />
                <x-ui.input
                    wire:model="label"
                    label="Label"
                    placeholder="e.g. Festival Advance Recovery"
                />
                <x-ui.input
                    wire:model="amount"
                    label="Amount (₹)"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                />
                <x-ui.input
                    wire:model="note"
                    label="Note (optional)"
                    placeholder="Reason or reference"
                />
            </div>

            <div class="mt-4 flex items-center gap-3">
                <x-ui.button wire:click="save">
                    {{ $editingId ? 'Update Adjustment' : 'Save Adjustment' }}
                </x-ui.button>
                @if ($editingId)
                    <x-ui.button variant="secondary" wire:click="cancelEdit">Cancel</x-ui.button>
                @endif
            </div>
        </x-ui.card>
    @else
        <div class="mt-6 border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            This batch is not editable for your role right now.
        </div>
    @endif

    {{-- immutable log --}}
    <x-ui.card class="mt-6" title="Adjustment Audit Log" description="Every payroll edit is kept here even if the current adjustment is later changed.">
        @if ($adjustmentLogs->isEmpty())
            <p class="py-6 text-center text-sm text-slate-400">No adjustment activity has been logged yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($adjustmentLogs as $log)
                    @php
                        $logLabel = match($log->action) {
                            'adjustment_created' => 'Adjustment Added',
                            'adjustment_updated' => 'Adjustment Updated',
                            'adjustment_deleted' => 'Adjustment Removed',
                            'disbursement_updated' => 'Disbursement Updated',
                            'disbursement_reset' => 'Disbursement Reset',
                            'leave_review_saved' => 'Leave Review Saved',
                            'leave_review_reset' => 'Leave Review Reset',
                            default => str($log->action)->replace('_', ' ')->title()->toString(),
                        };
                        $netDelta = (float) ($log->new_item_net_salary ?? 0) - (float) ($log->old_item_net_salary ?? 0);
                    @endphp
                    <div class="border border-slate-100 bg-slate-50 px-3 py-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ $logLabel }}</span>
                            <span class="text-xs text-slate-400">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span>{{ $log->actor?->name ?? 'System' }}</span>
                            <span>· {{ \App\Models\Role::labelFor($log->role) }}</span>
                            @if ($log->workflowStep)
                                <span>· {{ $log->workflowStep->name }}</span>
                            @endif
                            @if ($log->old_item_net_salary !== null && $log->new_item_net_salary !== null)
                                <span class="{{ $netDelta < 0 ? 'text-red-600' : ($netDelta > 0 ? 'text-green-700' : 'text-slate-500') }}">
                                    · Net {{ $netDelta >= 0 ? '+' : '' }}₹{{ number_format($netDelta, 2) }}
                                </span>
                            @endif
                        </div>
                        @if ($log->remarks)
                            <p class="mt-1 text-xs text-slate-600">{{ $log->remarks }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

</section>
