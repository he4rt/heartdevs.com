@props([
    'circular' => true,
    'size' => 'md',
    'src' => '',
    'alt' => '',
])

<img
    {{
        $attributes->class([
            'hp-avatar',
            'hp-circular' => $circular,
            'hp-size-' . $size,
        ])
    }}
    alt="{{ $alt }}"
    src="{{ $src }}"
/>
