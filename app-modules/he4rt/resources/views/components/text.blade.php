@props([
    'size' => 'md',
])

@php
    $classes = collect([
        'hp-text',
        'hp-text-' . $size,
    ])
        ->filter()
        ->implode(' ');
@endphp

<p {{ $attributes->class($classes) }}>
    {{ $slot }}
</p>
