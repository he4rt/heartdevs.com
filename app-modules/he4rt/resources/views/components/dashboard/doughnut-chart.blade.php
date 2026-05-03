@props ([
    'items',
    'total' => null,
    'size' => 130,
    'strokeWidth' => 16,
    'radius' => 55,
    'hideZero' => true,
    'primaryMetric' => 'count'
])

@php
    $items = collect($items);
    $total = $total ?? $items->sum('count');

    if ($hideZero) {
        $items = $items->filter(fn($i) => $i['count'] > 0)->values();
    }

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
    <svg viewBox="0 0 140 140" @class (['shrink-0', 'h-28 w-28' => $size <= 130])>
        <circle
            cx="{{ $cx }}"
            cy="{{ $cy }}"
            r="{{ $radius }}"
            fill="none"
            stroke-width="{{ $strokeWidth }}"
            class="stroke-zinc-100 dark:stroke-zinc-700/60"
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
            y="{{ $cy + 5 }}"
            text-anchor="middle"
            class="fill-zinc-900 dark:fill-white"
            font-size="22"
            font-weight="800"
        >
            {{ $total }}
        </text>
    </svg>

    <div class="flex-1 space-y-1">
        @foreach ($items as $idx => $item)
            @php
                $pct = $total > 0 ? round(($item['count'] / $total) * 100) : 0;
                $isPrimary = $primaryMetric === 'count';
            @endphp
            <div class="flex items-center justify-between py-0.5">
                <div class="flex items-center gap-2">
                    <div
                        class="h-2.5 w-2.5 rounded-sm ring-1 ring-white/10"
                        style="background:{{ $item['color'] }}"
                    ></div>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $item['label'] }}</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="font-mono text-sm font-bold text-zinc-900 dark:text-white"
                        >{{ $isPrimary ? $item['count'] : $pct . '%' }}</span
                    >
                    <span
                        class="font-mono text-[11px] text-zinc-400"
                        >{{ $isPrimary ? $pct . '%' : $item['count'] }}</span
                    >
                </div>
            </div>
        @endforeach
    </div>
</div>
