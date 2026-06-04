@props([
    'label' => '',
    'value' => '',
    'hint'  => null,
    'variant' => 'default',  // default | success | warning | danger | info
])

@php
$accents = [
    'default' => 'bg-slate-100 text-slate-500',
    'success' => 'bg-green-50 text-green-600',
    'warning' => 'bg-amber-50 text-amber-600',
    'danger'  => 'bg-red-50 text-red-600',
    'info'    => 'bg-blue-50 text-blue-600',
];
@endphp

<div class="border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
