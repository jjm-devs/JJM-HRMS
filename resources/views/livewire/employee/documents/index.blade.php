<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Documents"
        description="Submit and track your service documents."
    />

    @if (session('document_status'))
        <x-ui.alert variant="success" class="mt-5">{{ session('document_status') }}</x-ui.alert>
    @endif

    <div class="mt-5 grid gap-5 xl:grid-cols-[24rem_minmax(0,1fr)]">
        <x-ui.card title="Submit Document" description="Uploaded documents are sent to HR for verification.">
            <form wire:submit="submitDocument" class="space-y-4">
                <x-ui.select
                    wire:model="documentForm.document_type_id"
                    label="Document Type"
                    :options="$documentTypeOptions"
                    placeholder="General document"
                    :error="$errors->first('documentForm.document_type_id')"
                />

                <x-ui.input
                    wire:model="documentForm.title"
                    label="Title"
                    placeholder="Aadhaar, PAN, certificate, appointment order"
                    :error="$errors->first('documentForm.title')"
                    required
                />

                <x-ui.input
                    wire:model="documentForm.expires_at"
                    type="date"
                    label="Expiry Date"
                    :error="$errors->first('documentForm.expires_at')"
                />

                <x-ui.textarea
                    wire:model="documentForm.remarks"
                    label="Remarks"
                    rows="3"
                    :error="$errors->first('documentForm.remarks')"
                />

                <div>
                    <label class="text-sm font-medium text-slate-800">File <span class="text-red-500">*</span></label>
                    <input
                        type="file"
                        wire:model="documentFile"
                        class="mt-1 block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                    />
                    <p class="mt-1 text-xs text-slate-500">PDF, image, Word file up to 10 MB.</p>
                    @error('documentFile')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" variant="secondary" wire:click="resetDocumentForm">Reset</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitDocument">Submit</span>
                        <span wire:loading wire:target="submitDocument">Uploading...</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card label="Submitted" :value="$documents->where('status', 'submitted')->count()" hint="Awaiting HR" variant="warning" />
                <x-ui.stat-card label="Verified" :value="$documents->where('status', 'verified')->count()" hint="Accepted by HR" variant="success" />
                <x-ui.stat-card label="Total" :value="$documents->count()" hint="Your documents" />
            </div>

            <x-ui.card title="My Documents">
                @if ($documents->isEmpty())
                    <x-ui.empty-state title="No documents submitted" description="Submit your first document using the form." />
                @else
                    <x-ui.table :headers="['Document', 'Type', 'Status', 'Submitted', 'Verification', '']">
                        @foreach ($documents as $document)
                            <tr class="transition hover:bg-slate-50">
                                <x-ui.table.td>
                                    <span class="font-medium text-slate-900">{{ $document->title }}</span>
                                    <p class="text-xs text-slate-400">{{ $document->file_name }}</p>
                                    @if ($document->expires_at)
                                        <p class="text-xs text-slate-400">Expires {{ $document->expires_at->format('d M Y') }}</p>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>{{ $document->documentType?->name ?? 'General' }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :variant="$document->status === 'verified' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'warning')">
                                        {{ str_replace('_', ' ', ucfirst($document->status)) }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span class="text-sm text-slate-600">{{ $document->created_at->format('d M Y') }}</span>
                                    <p class="text-xs text-slate-400">{{ number_format($document->file_size / 1024, 1) }} KB</p>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if ($document->verified_at)
                                        <span class="text-sm text-slate-600">{{ $document->verified_at->format('d M Y') }}</span>
                                        <p class="text-xs text-slate-400">{{ $document->verifiedBy?->name ?? 'HR' }}</p>
                                    @else
                                        <span class="text-xs text-slate-400">Pending</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex flex-col gap-1">
                                        <button
                                            type="button"
                                            wire:click="downloadDocument({{ $document->id }})"
                                            class="text-left text-sm font-medium text-blue-700 hover:underline"
                                        >
                                            Download
                                        </button>
                                        @if (in_array($document->status, ['submitted', 'uploaded', 'rejected'], true))
                                            <button
                                                type="button"
                                                wire:click="deleteDocument({{ $document->id }})"
                                                wire:confirm="Remove this document?"
                                                class="text-left text-sm font-medium text-red-600 hover:underline"
                                            >
                                                Remove
                                            </button>
                                        @endif
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>
    </div>
</section>
