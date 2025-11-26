@props([
    'icon' => 'fas-fire-flame-curved',
    'as' => 'div',
    'interactive' => true,
    'size' => 'lg',
])

@php
    if ($attributes->has('href')) {
        $as = 'a';
    }

    $iconSizeCls = 'hp-icon-size-' . $size;
@endphp

<{{ $as }}
    {{
        $attributes->class([
            'hp-icon',
            'hp-icon-interactive' => $interactive,
        ])
    }}
>
    <x-filament::icon :icon="$icon" class="{{ $iconSizeCls }}" />
</{{ $as }}>
