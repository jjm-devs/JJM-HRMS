<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header title="Transfers" description="Move staff between offices and keep a posting history.">
        <x-ui.button wire:click="openModal" variant="primary">
            New Transfer
        </x-ui.button>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card class="mb-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                label="Search"
                placeholder="Employee name or code"
            />

            <x-ui.select
                wire:model.live="filterType"
                label="Transfer type"
                :options="$typeOptions"
                placeholder="All types"
            />
        </div>
    </x-ui.card>

    <x-ui.table :headers="['Employee', 'From', 'To', 'Type', 'Effective', 'Recorded by', 'Status']">
        @forelse ($transfers as $transfer)
            <tr class="transition hover:bg-slate-50">
                <x-ui.table.td>
                    <div class="font-medium text-slate-800">{{ $transfer->employee?->full_name ?? '—' }}</div>
                    <div class="text-xs text-slate-400">{{ $transfer->employee?->employee_code }}</div>
                </x-ui.table.td>
                <x-ui.table.td muted>{{ $transfer->fromOrgUnit?->name ?? '—' }}</x-ui.table.td>
                <x-ui.table.td>{{ $transfer->toOrgUnit?->name ?? '—' }}</x-ui.table.td>
                <x-ui.table.td>
                    <x-ui.badge variant="info">{{ $typeOptions[$transfer->transfer_type] ?? ucfirst($transfer->transfer_type) }}</x-ui.badge>
                </x-ui.table.td>
                <x-ui.table.td muted>{{ $transfer->effective_date?->format('d M Y') ?? '—' }}</x-ui.table.td>
                <x-ui.table.td muted>{{ $transfer->initiatedBy?->name ?? '—' }}</x-ui.table.td>
                <x-ui.table.td>
                    <x-ui.badge variant="success">Completed</x-ui.badge>
                </x-ui.table.td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10">
                    <x-ui.empty-state
                        title="No transfers yet"
                        description="Record a transfer to move a staff member to another office."
                    />
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $transfers->links() }}
    </div>

    {{-- Record transfer modal --}}
    <x-ui.modal name="record-transfer" title="Record a Transfer" size="lg">
        <form wire:submit="saveTransfer" class="space-y-4">
            <x-ui.select
                wire:model="form.employee_id"
                label="Employee"
                :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->employee_code.') — '.($e->orgUnit?->name ?? 'No office')])->all()"
                :error="$errors->first('form.employee_id')"
                placeholder="Select employee"
                required
            />

            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.select
                    wire:model="form.to_org_unit_id"
                    label="Transfer to office"
                    :options="$orgUnits->pluck('name', 'id')->all()"
                    :error="$errors->first('form.to_org_unit_id')"
                    placeholder="Select destination office"
                    required
                />

                <x-ui.select
                    wire:model="form.transfer_type"
                    label="Transfer type"
                    :options="$typeOptions"
                    :error="$errors->first('form.transfer_type')"
                    required
                />
            </div>

            <x-ui.input
                wire:model="form.effective_date"
                type="date"
                label="Effective date"
                :error="$errors->first('form.effective_date')"
                required
            />

            <x-ui.textarea
                wire:model="form.reason"
                label="Reason"
                :error="$errors->first('form.reason')"
                placeholder="Why is this transfer happening? (optional)"
            />

            <x-ui.textarea
                wire:model="form.remarks"
                label="Remarks"
                :error="$errors->first('form.remarks')"
                placeholder="Any additional notes (optional)"
            />

            <p class="text-xs text-slate-400">
                Saving will immediately move the employee to the selected office and update their posting history.
            </p>
        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'record-transfer' })">Cancel</x-ui.button>
            <x-ui.button variant="primary" wire:click="saveTransfer">Save Transfer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</section>
