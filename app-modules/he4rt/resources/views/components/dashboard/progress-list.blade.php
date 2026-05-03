@props (['items', 'total' => null, 'order' => 'desc'])

@php
    $collection = collect($items);

    $collection = match ($order) {
        'asc' => $collection->sortBy('count')->values(),
        'none' => $collection,
        default => $collection->sortByDesc('count')->values(),
    };

    $total = $total ?? $collection->sum('count');
    $maxCount = $collection->max('count') ?: 1;
@endphp

<div
    {{
        $attributes->class(
            'hp-dashboard-progress-list space-y-3',
        )
    }}
>
    @foreach ($collection as $item)
        @php
            $pct = $total > 0 ? round(($item['count'] / $total) * 100) : 0;
            $barWidth = round(($item['count'] / $maxCount) * 100);
        @endphp
        <div>
            <div class="mb-1 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full" style="background:{{ $item['color'] }}"></div>
                    <span class="text-sm font-medium dark:text-zinc-200">{{ $item['label'] }}</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="font-mono text-sm font-semibold dark:text-white">{{ $item['count'] }}</span>
                    <span class="font-mono text-[11px] text-zinc-400">{{ $pct }}%</span>
                </div>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700/50">
                <div
                    class="h-full rounded-full transition-all duration-700"
                    style="width:{{ max($barWidth, 2) }}%;background:{{ $item['color'] }}"
                ></div>
            </div>
        </div>
    @endforeach
</div>
