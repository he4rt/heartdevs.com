@props ([
    'columns' => 4 // 2 | 3 | 4 | 5
])

@php
    $gridClass = match ((int) $columns) {
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        5 => 'grid-cols-2 lg:grid-cols-5',
        default => 'grid-cols-2 lg:grid-cols-4',
    };
@endphp

<div
    {{
        $attributes->class([
            'hp-dashboard-stat-row grid gap-4',
            $gridClass,
        ])
    }}
>
    {{ $slot }}
</div>
