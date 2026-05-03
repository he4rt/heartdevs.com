@props (['items', 'max' => null, 'ranked' => true, 'labelWidth' => 'w-24', 'order' => 'desc'])

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
                <div
                    class="h-full rounded transition-all duration-700"
                    style="width:{{ max($pct, 2) }}%;background:{{ $item['color'] }}"
                ></div>
            </div>
            <span class="w-10 shrink-0 text-right font-mono text-xs font-semibold text-zinc-400">{{ $display }}</span>
        </div>
    @endforeach
</div>
