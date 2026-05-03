@props ([
    'items',
    'max' => null,
    'ranked' => true,
    'labelWidth' => 'w-28',
    'normalizeToTotal' => true,
    'groupTies' => true
])

@php
    $items = collect($items);
    $totalSum = $items->sum('value');
    $maxVal = $normalizeToTotal ? max($totalSum, 1) : max($max ?? $items->max('value'), 1);

    $prevValue = null;
    $prevRank = 0;
@endphp

<div
    {{
        $attributes->class(
            'hp-dashboard-bar-chart space-y-1.5',
        )
    }}
>
    @foreach ($items as $item)
        @php
            $pct = round(($item['value'] / $maxVal) * 100);
            $suffix = $item['suffix'] ?? '%';
            $displayValue = $item['value'] . $suffix;
            $isOther = strtolower($item['label'] ?? '') === 'outro' || strtolower($item['label'] ?? '') === 'other';

            if ($ranked && $groupTies) {
                $rank = $item['value'] === $prevValue ? $prevRank : $loop->iteration;
                $prevValue = $item['value'];
                $prevRank = $rank;
            } else {
                $rank = $loop->iteration;
            }
        @endphp
        <div @class (['flex items-center gap-2.5', 'opacity-50' => $isOther && $ranked])>
            @if ($ranked)
                <span
                    @class ([
                        'w-5 text-center font-mono text-xs font-bold',
                        'text-zinc-300 dark:text-zinc-600' => $isOther,
                        'text-zinc-400 dark:text-zinc-500' => !$isOther
                    ])
                    >{{ $isOther ? '·' : $rank }}</span
                >
            @endif
            <span
                class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200 {{ $labelWidth }}"
                title="{{ $item['label'] }}"
                >{{ $item['label'] }}</span
            >
            <div class="h-6 flex-1 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-700/50">
                <div
                    class="flex h-full items-center justify-end rounded-md pr-2.5 text-[11px] font-bold text-white transition-all duration-700"
                    style="width:{{ max($pct, 2) }}%;background:{{ $item['color'] }}"
                >
                    @if ($pct > 15) {{ $displayValue }}@endif
                </div>
            </div>
            @if ($pct <= 15)
                <span
                    class="w-10 text-right font-mono text-xs font-semibold text-zinc-500 dark:text-zinc-400"
                    >{{ $displayValue }}</span
                >
            @endif
        </div>
    @endforeach
</div>
