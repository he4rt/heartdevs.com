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
            match ($size) {
                'sm', 'md', 'lg' => "hp-size-{$size}",
                default => $size,
            },
        ])
    }}
    alt="{{ $alt }}"
    src="{{ $src }}"
/>
