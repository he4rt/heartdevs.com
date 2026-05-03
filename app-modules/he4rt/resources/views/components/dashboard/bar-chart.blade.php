@props ([
    'items', // array of ['label' => string, 'value' => int|float, 'color' => hex, 'suffix' => '%']
    'max' => null, // auto-calculated if null
    'ranked' => true,
    'labelWidth' => 'w-24'
])

@php
    $maxVal = $max ?? collect($items)->max('value');
    $maxVal = max($maxVal, 1);
@endphp

<div {{ $attributes->class('hp-dashboard-bar-chart space-y-2') }}>
    @foreach ($items as $item)
        @php
            $pct = round(($item['value'] / $maxVal) * 100);
            $suffix = $item['suffix'] ?? '%';
            $display = $item['value'] . $suffix;
        @endphp
        <div class="flex items-center gap-3">
            @if ($ranked)
                <span class="w-5 text-center font-mono text-xs font-bold text-zinc-400">{{ $loop->iteration }}</span>
            @endif
            <span class="truncate text-sm font-medium dark:text-zinc-200 {{ $labelWidth }}">{{ $item['label'] }}</span>
            <div class="h-5 flex-1 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-700">
                <div
                    class="flex h-full items-center justify-end rounded pr-2 text-xs font-semibold text-white transition-all duration-700"
                    style="width:{{ $pct }}%;background:{{ $item['color'] }}"
                >
                    @if ($pct > 12) {{ $display }}@endif
                </div>
            </div>
            @if ($pct <= 12)
                <span class="w-8 font-mono text-xs text-zinc-400">{{ $display }}</span>
            @endif
        </div>
    @endforeach
</div>
