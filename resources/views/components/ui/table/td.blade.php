@props([
    'muted' => false,
])

<td {{ $attributes->merge(['class' => 'px-4 py-3 ' . ($muted ? 'text-slate-400' : 'text-slate-700')]) }}>
    {{ $slot }}
</td>
