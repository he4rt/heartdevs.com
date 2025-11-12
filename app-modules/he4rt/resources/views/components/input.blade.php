@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'disabled' => false,
])

@php
    $inputId = $id ?? ($name ?? 'hp-input-' . \Illuminate\Support\Str::random(4));
    $isDisabled = (bool) $disabled;
@endphp

<div {{ $attributes->class('hp-input-field') }}>
    @if ($label)
        <label for="{{ $inputId }}" class="hp-input-label">
            {{ $label }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $inputId }}"
        @if ($isDisabled) disabled @endif
        {{ $attributes->class('hp-input') }}
    />
</div>
