<div class="mt-5 space-y-5">
    @if (session('family_status'))
        <x-ui.alert variant="success">{{ session('family_status') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <x-ui.table :headers="['Name', 'Relationship', 'Details', 'Dependent', 'Nominee', '']">
            @forelse ($employee->familyMembers as $familyMember)
                <tr class="transition hover:bg-slate-50">
                    <x-ui.table.td>
                        <div class="font-medium text-slate-900">{{ $familyMember->name }}</div>
                        <div class="text-xs text-slate-500">{{ $familyMember->gender ? ucfirst($familyMember->gender) : '-' }}</div>
                    </x-ui.table.td>
                    <x-ui.table.td>{{ $relationshipOptions[$familyMember->relationship] ?? ucfirst($familyMember->relationship) }}</x-ui.table.td>
                    <x-ui.table.td>
                        <div>{{ $familyMember->date_of_birth?->format('d M Y') ?? '-' }}</div>
                        <div class="text-xs text-slate-500">{{ $familyMember->mobile ?: $familyMember->occupation ?: '-' }}</div>
                    </x-ui.table.td>
                    <x-ui.table.td>
                        @if ($familyMember->is_dependent)
                            <x-ui.badge variant="success">Dependent</x-ui.badge>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td>
                        @if ($familyMember->is_nominee)
                            <x-ui.badge variant="info">{{ $familyMember->nominee_share }}%</x-ui.badge>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td>
                        <div class="flex items-center gap-1">
                            <x-ui.button wire:click="editFamilyMember({{ $familyMember->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteFamilyMember({{ $familyMember->id }})" variant="ghost" size="sm">Delete</x-ui.button>
                        </div>
                    </x-ui.table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state
                            title="No family members added"
                            description="Add dependents, nominees, and emergency family details."
                        />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <x-ui.card :title="$editingFamilyMemberId ? 'Edit Family Member' : 'Add Family Member'">
            <form wire:submit="saveFamilyMember" class="space-y-4">
                <x-ui.input
                    wire:model="familyForm.name"
                    label="Name"
                    :error="$errors->first('familyForm.name')"
                    required
                />

                <x-ui.select
                    wire:model="familyForm.relationship"
                    label="Relationship"
                    :options="$relationshipOptions"
                    :error="$errors->first('familyForm.relationship')"
                    required
                />

                <x-ui.input
                    wire:model="familyForm.date_of_birth"
                    type="date"
                    label="Date of Birth"
                    :error="$errors->first('familyForm.date_of_birth')"
                />

                <x-ui.select
                    wire:model="familyForm.gender"
                    label="Gender"
                    :options="$familyGenderOptions"
                    :error="$errors->first('familyForm.gender')"
                />

                <x-ui.input
                    wire:model="familyForm.mobile"
                    label="Mobile"
                    :error="$errors->first('familyForm.mobile')"
                />

                <x-ui.input
                    wire:model="familyForm.occupation"
                    label="Occupation"
                    :error="$errors->first('familyForm.occupation')"
                />

                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input
                            wire:model="familyForm.is_dependent"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                        >
                        Dependent
                    </label>

                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input
                            wire:model.live="familyForm.is_nominee"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600"
                        >
                        Nominee
                    </label>
                </div>

                @if ($familyForm['is_nominee'] ?? false)
                    <x-ui.input
                        wire:model="familyForm.nominee_share"
                        type="number"
                        label="Nominee Share"
                        hint="Enter percentage between 0 and 100."
                        :error="$errors->first('familyForm.nominee_share')"
                        required
                    />
                @endif

                <div class="flex items-center justify-end gap-2">
                    @if ($editingFamilyMemberId)
                        <x-ui.button wire:click="resetFamilyForm" variant="outline">Cancel</x-ui.button>
                    @endif

                    <x-ui.button type="submit" variant="primary">
                        {{ $editingFamilyMemberId ? 'Update Member' : 'Add Member' }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
