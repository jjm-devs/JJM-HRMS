<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Policies"
        description="Upload and manage official HR policy documents for employees."
    >
        <x-ui.button wire:click="$dispatch('open-modal', { name: 'upload-policy' })" variant="primary">
            Upload Policy
        </x-ui.button>
    </x-ui.page-header>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card class="mb-5">
        <div class="grid gap-3 sm:grid-cols-2">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                label="Search"
                placeholder="Search policy title"
            />
            <x-ui.select
                wire:model.live="category"
                label="Category"
                :options="$categoryOptions"
                placeholder="All categories"
            />
        </div>
    </x-ui.card>

    <x-ui.table :headers="['Title', 'Category', 'Status', 'Uploaded By', 'Date', '']">
        @forelse ($policies as $policy)
            <tr class="transition hover:bg-slate-50">
                <x-ui.table.td>
                    <span class="font-medium text-slate-900">{{ $policy->title }}</span>
                </x-ui.table.td>
                <x-ui.table.td>
                    {{ \App\Models\HrPolicy::CATEGORIES[$policy->category] ?? ucfirst($policy->category) }}
                </x-ui.table.td>
                <x-ui.table.td>
                    <x-ui.badge :variant="$policy->is_active ? 'success' : 'default'">
                        {{ $policy->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                </x-ui.table.td>
                <x-ui.table.td>{{ $policy->uploader?->name ?? '-' }}</x-ui.table.td>
                <x-ui.table.td>{{ $policy->created_at->format('d M Y') }}</x-ui.table.td>
                <x-ui.table.td>
                    <div class="flex items-center gap-1">
                        <x-ui.button wire:click="download({{ $policy->id }})" variant="ghost" size="sm">Download</x-ui.button>
                        <x-ui.button wire:click="toggleActive({{ $policy->id }})" variant="ghost" size="sm">
                            {{ $policy->is_active ? 'Deactivate' : 'Activate' }}
                        </x-ui.button>
                        <x-ui.button wire:click="confirmDelete({{ $policy->id }})" variant="ghost" size="sm">Delete</x-ui.button>
                    </div>
                </x-ui.table.td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-ui.empty-state
                        title="No policies uploaded"
                        description="Upload official policy documents for employees to view."
                    />
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if ($policies->hasPages())
        <div class="mt-4">{{ $policies->links() }}</div>
    @endif

    {{-- Upload Modal --}}
    <x-ui.modal name="upload-policy" title="Upload Policy" size="lg">
        <div class="space-y-4">
            <x-ui.input
                wire:model="title"
                label="Title"
                placeholder="e.g. Leave Policy 2025"
                :error="$errors->first('title')"
                required
            />
            <x-ui.select
                wire:model="uploadCategory"
                label="Category"
                :options="$categoryOptions"
                placeholder="Select category"
                :error="$errors->first('uploadCategory')"
                required
            />
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-800">PDF File <span class="text-red-500">*</span></label>
                <input
                    type="file"
                    wire:model="file"
                    accept=".pdf"
                    class="block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                />
                <p class="text-xs text-slate-500">PDF only, up to 10 MB.</p>
                @error('file')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="isActive" id="is_active" class="h-4 w-4">
                <label for="is_active" class="text-sm text-slate-700">Set as active (visible to employees)</label>
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'upload-policy' })">Cancel</x-ui.button>
            <x-ui.button wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Upload</span>
                <span wire:loading wire:target="save">Uploading...</span>
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Delete Confirm Modal --}}
    <x-ui.modal name="confirm-delete" title="Delete Policy">
        <p class="text-sm text-slate-600">Are you sure you want to delete this policy? This action cannot be undone.</p>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })">Cancel</x-ui.button>
            <x-ui.button wire:click="delete" variant="danger">Delete</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</section>