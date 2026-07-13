@php
    $addressTypes = ['current_address', 'permanent_address'];
    $pointContacts = $employee->contacts->whereNotIn('type', $addressTypes);
    $addressContacts = $employee->contacts->whereIn('type', $addressTypes);
    $isEditingAddress = in_array($contactForm['type'], $addressTypes, true);
@endphp

<div class="mt-5 space-y-6">
    @if (session('contact_status'))
        <x-ui.alert variant="success">{{ session('contact_status') }}</x-ui.alert>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        {{-- Phone / email / emergency contacts --}}
        <x-ui.table :headers="['Type', 'Label', 'Details', 'Primary', '']">
            @forelse ($pointContacts as $contact)
                <tr class="transition hover:bg-slate-50">
                    <x-ui.table.td>{{ $contactTypeOptions[$contact->type] ?? $contact->type }}</x-ui.table.td>
                    <x-ui.table.td>{{ $contact->label ?: '-' }}</x-ui.table.td>
                    <x-ui.table.td>{{ $contact->value ?: '-' }}</x-ui.table.td>
                    <x-ui.table.td>
                        @if ($contact->is_primary)
                            <x-ui.badge variant="success">Primary</x-ui.badge>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </x-ui.table.td>
                    <x-ui.table.td>
                        <div class="flex items-center gap-1">
                            <x-ui.button wire:click="editContact({{ $contact->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteContact({{ $contact->id }})" variant="ghost" size="sm">Delete</x-ui.button>
                        </div>
                    </x-ui.table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-ui.empty-state
                            title="No phone or email contacts"
                            description="Add a mobile, email, or emergency contact using the form."
                        />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        {{-- Add / edit form --}}
        <x-ui.card :title="$editingContactId ? 'Edit Contact' : 'Add Contact'">
            <form wire:submit="saveContact" class="space-y-4">
                <x-ui.select
                    wire:model.live="contactForm.type"
                    label="Contact Type"
                    :options="$contactTypeOptions"
                    :error="$errors->first('contactForm.type')"
                    required
                />

                <x-ui.input
                    wire:model="contactForm.label"
                    label="Label"
                    placeholder="Office, personal, emergency"
                    :error="$errors->first('contactForm.label')"
                />

                @unless ($isEditingAddress)
                    <x-ui.input
                        wire:model="contactForm.value"
                        label="Contact Value"
                        placeholder="Mobile, email, or emergency contact"
                        :error="$errors->first('contactForm.value')"
                    />
                @else
                    <x-ui.input wire:model="contactForm.address_line_1" label="Address Line 1" :error="$errors->first('contactForm.address_line_1')" required />
                    <x-ui.input wire:model="contactForm.address_line_2" label="Address Line 2" :error="$errors->first('contactForm.address_line_2')" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.input wire:model="contactForm.city" label="City" :error="$errors->first('contactForm.city')" />
                        <x-ui.input wire:model="contactForm.district" label="District" :error="$errors->first('contactForm.district')" />
                        <x-ui.input wire:model="contactForm.state" label="State" :error="$errors->first('contactForm.state')" />
                        <x-ui.input wire:model="contactForm.pincode" label="Pincode" :error="$errors->first('contactForm.pincode')" />
                    </div>
                @endunless

                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input wire:model="contactForm.is_primary" type="checkbox" class="h-4 w-4 border-slate-300 text-blue-700 focus:ring-blue-600">
                    Primary for this contact type
                </label>

                <div class="flex items-center justify-end gap-2">
                    @if ($editingContactId)
                        <x-ui.button wire:click="resetContactForm" variant="outline">Cancel</x-ui.button>
                    @endif

                    <x-ui.button type="submit" variant="primary">
                        {{ $editingContactId ? 'Update Contact' : 'Add Contact' }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    {{-- Addresses (shown as cards, full width) --}}
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-700">Addresses</h3>

        @if ($addressContacts->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($addressContacts as $addressContact)
                    <div class="border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-sm font-semibold text-slate-900">
                                {{ $contactTypeOptions[$addressContact->type] ?? $addressContact->type }}
                                @if ($addressContact->label)
                                    <span class="font-normal text-slate-500">({{ $addressContact->label }})</span>
                                @endif
                            </h4>

                            @if ($addressContact->is_primary)
                                <x-ui.badge variant="success">Primary</x-ui.badge>
                            @endif
                        </div>

                        <address class="mt-2 text-sm not-italic leading-relaxed text-slate-700">
                            @if ($addressContact->address_line_1)
                                {{ $addressContact->address_line_1 }}<br>
                            @endif
                            @if ($addressContact->address_line_2)
                                {{ $addressContact->address_line_2 }}<br>
                            @endif
                            {{ collect([$addressContact->city, $addressContact->district])->filter()->implode(', ') }}
                            @if ($addressContact->city || $addressContact->district)
                                <br>
                            @endif
                            {{ collect([$addressContact->state, $addressContact->pincode])->filter()->implode(' - ') }}
                        </address>

                        <div class="mt-3 flex items-center gap-1">
                            <x-ui.button wire:click="editContact({{ $addressContact->id }})" variant="ghost" size="sm">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteContact({{ $addressContact->id }})" variant="ghost" size="sm">Delete</x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-slate-400">
                No addresses added yet. Pick "Current Address" or "Permanent Address" in the form to add one.
            </p>
        @endif
    </div>
</div>
