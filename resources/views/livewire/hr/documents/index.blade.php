<section class="mx-auto max-w-9xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Documents</h1>
            <p class="text-sm text-slate-500">Employee submissions, generated payroll letters, and uploaded batch attachments.</p>
        </div>
    </div>

    <x-ui.card class="mt-5">
        <div class="grid gap-3 lg:grid-cols-3">
            <x-ui.input
                wire:model.live.debounce.300ms="search"
                label="Search"
                placeholder="Document, employee, or batch"
            />
            <x-ui.select
                wire:model.live="ownerType"
                label="Document Area"
                :options="$ownerOptions"
                placeholder="All areas"
            />
            <x-ui.select
                wire:model.live="status"
                label="Status"
                :options="$statusOptions"
                placeholder="All statuses"
            />
        </div>
    </x-ui.card>

    <x-ui.card class="mt-5">
        @if ($documents->isEmpty())
            <p class="py-10 text-center text-sm text-slate-400">No documents found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Document</th>
                            <th class="px-3 py-2">Area</th>
                            <th class="px-3 py-2">Owner</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Uploaded By</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $document)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <span class="font-medium text-slate-900">{{ $document->title }}</span>
                                    <p class="text-xs text-slate-400">{{ $document->file_name }}</p>
                                    @if ($document->documentType)
                                        <p class="text-xs text-slate-400">{{ $document->documentType->name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $this->ownerTypeLabel($document) }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $this->ownerLabel($document) }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                        {{ str($document->status)->replace('_', ' ')->title() }}
                                    </span>
                                    <p class="text-xs text-slate-400">v{{ $document->version }}</p>
                                </td>
                                <td class="px-3 py-2 text-slate-600">{{ $document->uploadedBy?->name ?? 'System' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $document->created_at->format('d M Y, h:i A') }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if ($this->canDownloadDocument($document))
                                        <button
                                            type="button"
                                            wire:click="downloadDocument({{ $document->id }})"
                                            class="text-sm font-medium text-blue-700 hover:underline"
                                        >
                                            Download
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">HO only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        @endif
    </x-ui.card>
</section>
