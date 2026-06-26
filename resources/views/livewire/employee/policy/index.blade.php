<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Policies"
        description="Official HR policy documents for employees."
    >
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
                    </div>
                </x-ui.table.td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-ui.empty-state
                        title="No policies uploaded"
                        description="Official HR policy documents for employees."
                    />
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if ($policies->hasPages())
        <div class="mt-4">{{ $policies->links() }}</div>
    @endif

</section>