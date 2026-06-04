@props([
    'title'       => null,
    'description' => null,
    'padding'     => true,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 shadow-sm']) }}>
    @if ($title)
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @if ($description)
                <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="{{ $padding ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
