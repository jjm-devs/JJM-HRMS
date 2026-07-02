@props([
    'show'  => false,
    'title' => null,
    'size'  => 'md',  // sm | md | lg | xl
    'name' => null,
])

@php
$sizes = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
];
@endphp

<div
    x-data="{ open: @js($show) }"
    x-show="open"
    x-on:open-modal.window="if (! @js($name) || $event.detail?.name === @js($name)) open = true"
    x-on:close-modal.window="if (! @js($name) || $event.detail?.name === @js($name)) open = false"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        x-on:click="open = false"
    ></div>

    {{-- Dialog --}}
    <div
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative flex w-full flex-col {{ $sizes[$size] }} border border-slate-200 bg-white shadow-xl"
        style="max-height: calc(100vh - 2rem);"
    >
        @if ($title)
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                <button
                    x-on:click="open = false"
                    class="p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 justify-end gap-3 border-t border-slate-100 px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
