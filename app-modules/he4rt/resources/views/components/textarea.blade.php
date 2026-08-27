@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'disabled' => false,
    'rows' => 3,
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

    <textarea
        name="{{ $name }}"
        id="{{ $inputId }}"
        rows="{{ $rows }}"
        @if ($isDisabled) disabled @endif
        {{ $attributes->class('hp-input resize-none') }}
    ></textarea>
</div>
