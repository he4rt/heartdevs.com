@props([
    'icon' => 'heroicon-s-heart',
    'as' => 'div',
    'interactive' => true,
    'size' => 'lg',
])

@php
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
    <x-filament::icon :icon="$icon" {{ $attributes->class([$iconSizeCls]) }} />
</{{ $as }}>
