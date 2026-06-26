<section class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-900">Manage Signature</h1>
        <p class="mt-1 text-sm text-slate-500">
            Your signature will be printed on every payslip generated under your authority.
        </p>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" class="mb-5">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- current signature --}}
    <x-ui.card class="mb-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-700">Current Signature</h2>

        @if ($hasSignature && $signatureUrl)
            <div class="mb-4 border border-slate-200 bg-slate-50 p-4">
                <img src="{{ $signatureUrl }}" alt="Your signature" class="max-h-24 max-w-xs object-contain">
            </div>
            <x-ui.button
                variant="secondary"
                wire:click="remove"
                wire:confirm="Remove your signature? Payslips generated after this will not carry a signature."
            >
                Remove Signature
            </x-ui.button>
        @else
            <p class="py-6 text-center text-sm text-slate-400">No signature uploaded yet.</p>
        @endif
    </x-ui.card>

    {{-- upload --}}
    <x-ui.card>
        <h2 class="mb-1 text-sm font-semibold text-slate-700">
            {{ $hasSignature ? 'Replace Signature' : 'Upload Signature' }}
        </h2>
        <p class="mb-4 text-xs text-slate-400">
            Upload a clear image of your signature on a white background. JPG or PNG, max 2 MB.
        </p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Signature Image</label>
                <input
                    type="file"
                    wire:model="signatureFile"
                    accept="image/jpeg,image/png"
                    class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700"
                >
                @error('signatureFile')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if ($signatureFile)
                <div>
                    <p class="mb-1 text-xs text-slate-500">Preview</p>
                    <div class="border border-slate-200 bg-slate-50 p-4">
                        <img src="{{ $signatureFile->temporaryUrl() }}" alt="Preview" class="max-h-24 max-w-xs object-contain">
                    </div>
                </div>
            @endif

            <x-ui.button wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save Signature</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-ui.button>
        </div>
    </x-ui.card>

</section>
