@props ([
    'items',
    'max' => null,
    'ranked' => true,
    'labelWidth' => 'w-24',
    'order' => 'desc',
    'stacked' => false,
    'legend' => null
])

@php
    $collection = collect($items);

    $collection = match ($order) {
        'asc' => $collection->sortBy('value')->values(),
        'none' => $collection,
        default => $collection->sortByDesc('value')->values(),
    };

    $maxVal = $max ?? $collection->max('value');
    $maxVal = max($maxVal, 1);
@endphp

<div {{ $attributes->class('hp-dashboard-bar-chart space-y-2') }}>
    @foreach ($collection as $item)
        @php
            $pct = round(($item['value'] / $maxVal) * 100);
            $suffix = $item['suffix'] ?? '%';
            $display = $item['value'] . $suffix;
            $segments = $stacked ? $item['segments'] ?? [] : [];
        @endphp
        <div class="flex items-center gap-3">
            @if ($ranked)
                <span
                    class="w-5 shrink-0 text-center font-mono text-xs font-bold text-zinc-400"
                    >{{ $loop->iteration }}</span
                >
            @endif
            <span
                class="shrink-0 truncate text-sm font-medium dark:text-zinc-200 {{ $labelWidth }}"
                >{{ $item['label'] }}</span
            >
            <div class="h-5 min-w-0 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-700">
                @if ($stacked && count($segments) > 0)
                    <div class="flex h-full" style="width:{{ max($pct, 2) }}%">
                        @foreach ($segments as $seg)
                            @php
                                $segPct = $item['value'] > 0 ? ($seg['value'] / $item['value']) * 100 : 0;
                            @endphp
                            @if ($segPct > 0)
                                <div
                                    class="h-full transition-all duration-700 first:rounded-l last:rounded-r"
                                    style="width:{{ $segPct }}%;background:{{ $seg['color'] }}"
                                    title="{{ $seg['label'] ?? '' }}: {{ $seg['value'] }}"
                                ></div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div
                        class="h-full rounded transition-all duration-700"
                        style="width:{{ max($pct, 2) }}%;background:{{ $item['color'] }}"
                    ></div>
                @endif
            </div>
            <span class="w-10 shrink-0 text-right font-mono text-xs font-semibold text-zinc-400">{{ $display }}</span>
        </div>
    @endforeach

    @if ($stacked && $legend)
        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
            @foreach ($legend as $leg)
                <div class="flex items-center gap-1.5">
                    <div class="h-2.5 w-2.5 rounded-sm" style="background:{{ $leg['color'] }}"></div>
                    <span class="text-xs font-medium dark:text-zinc-300">{{ $leg['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
