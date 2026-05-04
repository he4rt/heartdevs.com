@props ([
    'columns' => 2 // 1 | 2 | 3
])

@php
    $gridClass = match ((int) $columns) {
        1 => 'grid-cols-1',
        3 => 'grid-cols-1 lg:grid-cols-3',
        default => 'grid-cols-1 lg:grid-cols-2',
    };
@endphp

<div
    {{
        $attributes->class([
            'hp-dashboard-grid grid gap-4',
            $gridClass,
        ])
    }}
>
    {{ $slot }}
</div>
