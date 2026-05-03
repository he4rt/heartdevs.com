@props ([
    'label',
    'value',
    'description' => null,
    'color' => 'zinc',
    'icon' => null,
    'trend' => null, // 'up' | 'down' | null
    'trendValue' => null
])

@php
    $accentMap = [
        'amber' => [
            'border' => 'border-t-amber-500',
            'text' => 'text-amber-500',
            'trend-up' => 'text-amber-400',
            'trend-down' => 'text-amber-400',
        ],
        'green' => [
            'border' => 'border-t-emerald-500',
            'text' => 'text-emerald-500',
            'trend-up' => 'text-emerald-400',
            'trend-down' => 'text-red-400',
        ],
        'blue' => [
            'border' => 'border-t-blue-500',
            'text' => 'text-blue-500',
            'trend-up' => 'text-blue-400',
            'trend-down' => 'text-blue-400',
        ],
        'red' => [
            'border' => 'border-t-red-500',
            'text' => 'text-red-500',
            'trend-up' => 'text-red-400',
            'trend-down' => 'text-emerald-400',
        ],
        'violet' => [
            'border' => 'border-t-violet-500',
            'text' => 'text-violet-500',
            'trend-up' => 'text-violet-400',
            'trend-down' => 'text-violet-400',
        ],
        'cyan' => [
            'border' => 'border-t-cyan-500',
            'text' => 'text-cyan-500',
            'trend-up' => 'text-cyan-400',
            'trend-down' => 'text-cyan-400',
        ],
        'orange' => [
            'border' => 'border-t-orange-500',
            'text' => 'text-orange-500',
            'trend-up' => 'text-orange-400',
            'trend-down' => 'text-orange-400',
        ],
        'pink' => [
            'border' => 'border-t-pink-500',
            'text' => 'text-pink-500',
            'trend-up' => 'text-pink-400',
            'trend-down' => 'text-pink-400',
        ],
        'zinc' => [
            'border' => 'border-t-zinc-500',
            'text' => 'text-zinc-500',
            'trend-up' => 'text-zinc-400',
            'trend-down' => 'text-zinc-400',
        ],
    ];
    $accent = $accentMap[$color] ?? $accentMap['zinc'];
@endphp

<div
    {{
        $attributes->class([
            'hp-dashboard-stat rounded-xl border border-zinc-200 border-t-2 bg-white p-4',
            'transition-all hover:-translate-y-0.5 hover:shadow-md',
            'dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-violet-600',
            $accent['border'],
        ])
    }}
>
    <div class="mb-2 flex items-center gap-1.5">
        @if ($icon)
            <flux:icon :name="$icon" class="h-3.5 w-3.5 text-zinc-400" variant="mini" />
        @endif
        <span class="text-[11px] font-semibold tracking-widest text-zinc-400 uppercase">{{ $label }}</span>
    </div>

    <div class="text-3xl font-black tracking-tight {{ $accent['text'] }}">{{ $value }}</div>

    @if ($description || $trend)
        <div class="mt-1 flex items-center gap-1.5">
            @if ($trend === 'up')
                <flux:icon name="arrow-trending-up" class="h-3.5 w-3.5 {{ $accent['trend-up'] }}" variant="mini" />
            @elseif ($trend === 'down')
                <flux:icon name="arrow-trending-down" class="h-3.5 w-3.5 {{ $accent['trend-down'] }}" variant="mini" />
            @endif
            <span class="text-xs text-zinc-400">
                @if ($trendValue)
                    <strong
                        class="{{ $trend === 'up' ? $accent['trend-up'] : $accent['trend-down'] }}"
                        >{{ $trendValue }}</strong
                    >
                @endif
                {{ $description }}
            </span>
        </div>
    @endif
</div>
