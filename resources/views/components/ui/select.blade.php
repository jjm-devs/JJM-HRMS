@props([
    'label'    => null,
    'hint'     => null,
    'error'    => null,
    'id'       => null,
    'required' => false,
    'options'  => [],   // ['value' => 'Label']
    'placeholder' => 'Select an option',
])

@php $id = $id ?? 'select-' . uniqid(); @endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-medium text-slate-800">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id }}"
        {{ $attributes->merge([
            'class' => 'block w-full border ' .
                ($error ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100') .
                ' bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition focus:ring-2 appearance-none'
        ]) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    @if ($hint && !$error)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
