@props([
    'variant' => 'info',  // info | success | warning | danger
    'title'   => null,
])

@php
$variants = [
    'info'    => 'bg-blue-50 border-blue-200 text-blue-800',
    'success' => 'bg-green-50 border-green-200 text-green-800',
    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
    'danger'  => 'bg-red-50 border-red-200 text-red-800',
];
@endphp

<div class="border px-4 py-3 text-sm {{ $variants[$variant] }}">
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
        <p class="mt-0.5 opacity-90">{{ $slot }}</p>
    @else
        {{ $slot }}
    @endif
</div>
