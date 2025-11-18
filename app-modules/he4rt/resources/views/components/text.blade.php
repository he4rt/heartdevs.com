@props([
    'size' => 'md',
])

@php
    $classes = collect([
        'hp-text',
        'hp-text-size-' . $size,
    ])
        ->filter()
        ->implode(' ');
@endphp

<p {{ $attributes->class($classes) }}>
    {{ $slot }}
</p>
