@props (['items', 'total' => null, 'height' => 'h-6', 'showLegend' => true, 'order' => 'desc'])

@php
    $collection = collect($items);

    $collection = match ($order) {
        'asc' => $collection->sortBy('count')->values(),
        'none' => $collection,
        default => $collection->sortByDesc('count')->values(),
    };

    $total = $total ?? $collection->sum('count');
@endphp

<div
    {{
        $attributes->class(
            'hp-dashboard-stacked-bar space-y-3',
        )
    }}
>
    <div class="flex {{ $height }} overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-700/50">
        @foreach ($collection as $item)
            @php $pct = $total > 0 ? ($item['count'] / $total) * 100 : 0; @endphp
            @if ($pct > 0)
                <div
                    class="relative transition-all duration-700 first:rounded-l-lg last:rounded-r-lg"
                    style="width:{{ $pct }}%;background:{{ $item['color'] }}"
                    title="{{ $item['label'] }}: {{ $item['count'] }} ({{ round($pct) }}%)"
                >
                    @if ($pct > 8)
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white drop-shadow-sm"
                            >{{ round($pct) }}%</span
                        >
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    @if ($showLegend)
        <div class="flex flex-wrap gap-x-4 gap-y-1.5">
            @foreach ($collection as $item)
                @php $pct = $total > 0 ? round(($item['count'] / $total) * 100) : 0; @endphp
                <div class="flex items-center gap-1.5">
                    <div class="h-2.5 w-2.5 rounded-sm" style="background:{{ $item['color'] }}"></div>
                    <span class="text-xs font-medium dark:text-zinc-300">{{ $item['label'] }}</span>
                    <span class="font-mono text-xs text-zinc-400">{{ $item['count'] }}</span>
                    <span class="font-mono text-[10px] text-zinc-500">({{ $pct }}%)</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
