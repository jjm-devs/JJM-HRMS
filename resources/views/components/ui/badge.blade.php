@props([
    'variant' => 'default', // default | success | warning | danger | info | purple
])

@php
$variants = [
    'default' => 'bg-slate-100 text-slate-600',
    'success' => 'bg-green-50 text-green-700',
    'warning' => 'bg-amber-50 text-amber-700',
    'danger'  => 'bg-red-50 text-red-700',
    'info'    => 'bg-blue-50 text-blue-700',
    'purple'  => 'bg-purple-50 text-purple-700',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium ' . $variants[$variant]]) }}>
    {{ $slot }}
</span>
