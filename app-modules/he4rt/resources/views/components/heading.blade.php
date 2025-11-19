@props([
    'level' => 1,
    'size' => 'md',
])

@php
    $tag = 'h' . max(1, min($level, 6));

    $classes = collect([
        'hp-heading',
        'hp-heading-size-' . $size,
    ])
        ->filter()
        ->implode(' ');
@endphp

<{{ $tag }} {{ $attributes->class($classes) }}>
    {{ $slot }}
</{{ $tag }}>
