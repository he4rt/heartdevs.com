@props ([
    'items',
    'total' => null,
    'size' => 130,
    'strokeWidth' => 16,
    'radius' => 55,
    'order' => 'desc'
])

@php
    $collection = collect($items);

    $collection = match ($order) {
        'asc' => $collection->sortBy('count')->values(),
        'none' => $collection,
        default => $collection->sortByDesc('count')->values(),
    };

    $items = $collection->toArray();
    $total = $total ?? $collection->sum('count');
    $circumference = 2 * M_PI * $radius;
    $offset = -($circumference * 0.25);
    $segments = [];
    foreach ($items as $item) {
        $pct = $total > 0 ? $item['count'] / $total : 0;
        $dash = $pct * $circumference;
        $segments[] = [
            'dash' => $dash,
            'gap' => $circumference - $dash,
            'offset' => $offset,
            'color' => $item['color'],
        ];
        $offset += $dash;
    }
    $center = $size / 2 + ($size - 130) / 2;
    $cx = 70;
    $cy = 70;
@endphp

<div
    {{
        $attributes->class(
            'hp-dashboard-doughnut flex items-center gap-6',
        )
    }}
>
    <svg viewBox="0 0 140 140" @class (['shrink-0', 'h-32 w-32' => $size === 130, 'h-28 w-28' => $size < 130])>
        <circle
            cx="{{ $cx }}"
            cy="{{ $cy }}"
            r="{{ $radius }}"
            fill="none"
            stroke-width="{{ $strokeWidth }}"
            class="stroke-zinc-100 dark:stroke-zinc-700"
        />
        @foreach ($segments as $seg)
            @if ($seg['dash'] > 0.5)
                <circle
                    cx="{{ $cx }}"
                    cy="{{ $cy }}"
                    r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $seg['color'] }}"
                    stroke-width="{{ $strokeWidth }}"
                    stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                    stroke-dashoffset="{{ $seg['offset'] }}"
                    stroke-linecap="round"
                    class="transition-all duration-700"
                />
            @endif
        @endforeach
        <text
            x="{{ $cx }}"
            y="{{ $cy - 4 }}"
            text-anchor="middle"
            class="fill-zinc-900 dark:fill-white"
            font-size="20"
            font-weight="800"
        >
            {{ $total }}
        </text>
        <text x="{{ $cx }}" y="{{ $cy + 10 }}" text-anchor="middle" class="fill-zinc-400" font-size="9">total</text>
    </svg>

    <div class="flex-1 space-y-1.5">
        @foreach ($items as $item)
            @php $pct = $total > 0 ? round(($item['count'] / $total) * 100) : 0; @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-sm" style="background:{{ $item['color'] }}"></div>
                    <span class="text-sm font-medium dark:text-zinc-200">{{ $item['label'] }}</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="font-mono text-sm font-semibold dark:text-white">{{ $item['count'] }}</span>
                    <span class="font-mono text-xs text-zinc-400">{{ $pct }}%</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
