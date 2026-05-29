@props([
    'title'       => '',
    'description' => null,
])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-lg font-semibold text-slate-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
