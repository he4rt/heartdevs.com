@props([
    'icon' => 'heroicon-s-heart',
    'as' => 'div',
    'interactive' => true,
])

<{{ $as }}
    {{
        $attributes->class([
            'bg-icon-high text-icon-dark rounded-md p-2',
            'cursor-pointer transition-transform duration-200 ease-in-out hover:scale-105 active:scale-95' => $interactive,
        ])
    }}
>
    <x-filament::icon :icon="$icon" class="h-6 w-6" />
</{{ $as }}>
