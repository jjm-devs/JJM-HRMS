@props([
    'variant' => 'primary',   // primary | secondary | danger | ghost | outline
    'size'    => 'md',        // sm | md | lg
    'type'    => 'button',
    'disabled' => false,
    'wire'    => null,        // e.g. "click='save'"
    'href' => null,
    'textcolor' => null,
])

@php
$base = 'inline-flex items-center justify-center gap-2 font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed';

$variants = [
    'primary'   => 'bg-green-700 text-[#fff] hover:bg-green-800 focus:ring-green-500',
    'secondary' => 'bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-slate-400',
    'danger'    => 'bg-red-600 text-[#fff] hover:bg-red-700 focus:ring-red-500',
    'ghost'     => 'bg-transparent text-slate-600 hover:bg-slate-100 focus:ring-slate-400',
    'outline'   => 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 focus:ring-slate-400 shadow-sm',
];

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-sm',
];

$textcolors = [
    'primary'   => 'text-[#fff]',
    'secondary' => 'text-slate-700',
    'danger'    => 'text-[#fff]',
    'ghost'     => 'text-slate-600',
    'outline'   => 'text-slate-700',
];

$resolvedTextColor = match($textcolor) {
    'primary'   => 'text-green-700',
    'secondary' => 'text-blue-700',
    'danger'    => 'text-red-600',
    default     => $textcolors[$variant] ?? 'text-slate-600',
};

$classes = $base . ' ' . $variants[$variant] . ' ' . $sizes[$size] . ' ' . $resolvedTextColor;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $wire ? "wire:$wire" : '' }}
        {{ $attributes->merge(['class' => $classes]) }}
    >
        {{ $slot }}
    </button>
@endif
