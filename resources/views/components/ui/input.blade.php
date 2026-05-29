@props([
    'label'       => null,
    'hint'        => null,
    'error'       => null,
    'type'        => 'text',
    'id'          => null,
    'required'    => false,
    'placeholder' => '',
])

@php $id = $id ?? 'input-' . uniqid(); @endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium text-slate-800">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-lg border ' .
                ($error ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100') .
                ' bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:ring-2'
        ]) }}
    >

    @if ($hint && !$error)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
