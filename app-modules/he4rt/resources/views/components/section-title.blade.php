@props([
    'size' => 'md',
    //lg,
])

@php
    $sizeCls = 'hp-section-title-size-' . $size;
@endphp

<div {{
    $attributes->class([
        'hp-section-title',
        $sizeCls,
    ])
}}>
    {{ $slot }}
</div>
