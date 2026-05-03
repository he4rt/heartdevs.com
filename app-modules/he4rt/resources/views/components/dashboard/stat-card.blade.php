@props ([
    'label',
    'value',
    'description' => null,
    'color' => 'zinc',
    'icon' => null,
    'trend' => null,
    'trendValue' => null
])

@php
    $accentMap = [
        'amber' => [
            'border' => 'border-t-amber-500',
            'bg' => 'bg-amber-500/10',
            'icon-bg' => 'bg-amber-500/15',
            'icon-text' => 'text-amber-400',
            'text' => 'text-amber-500 dark:text-amber-400',
            'trend-up' => 'text-amber-400',
            'trend-down' => 'text-amber-400',
        ],
        'green' => [
            'border' => 'border-t-emerald-500',
            'bg' => 'bg-emerald-500/10',
            'icon-bg' => 'bg-emerald-500/15',
            'icon-text' => 'text-emerald-400',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'trend-up' => 'text-emerald-400',
            'trend-down' => 'text-red-400',
        ],
        'blue' => [
            'border' => 'border-t-blue-500',
            'bg' => 'bg-blue-500/10',
            'icon-bg' => 'bg-blue-500/15',
            'icon-text' => 'text-blue-400',
            'text' => 'text-blue-600 dark:text-blue-400',
            'trend-up' => 'text-blue-400',
            'trend-down' => 'text-blue-400',
        ],
        'red' => [
            'border' => 'border-t-red-500',
            'bg' => 'bg-red-500/10',
            'icon-bg' => 'bg-red-500/15',
            'icon-text' => 'text-red-400',
            'text' => 'text-red-600 dark:text-red-400',
            'trend-up' => 'text-red-400',
            'trend-down' => 'text-emerald-400',
        ],
        'violet' => [
            'border' => 'border-t-violet-500',
            'bg' => 'bg-violet-500/10',
            'icon-bg' => 'bg-violet-500/15',
            'icon-text' => 'text-violet-400',
            'text' => 'text-violet-600 dark:text-violet-400',
            'trend-up' => 'text-violet-400',
            'trend-down' => 'text-violet-400',
        ],
        'cyan' => [
            'border' => 'border-t-cyan-500',
            'bg' => 'bg-cyan-500/10',
            'icon-bg' => 'bg-cyan-500/15',
            'icon-text' => 'text-cyan-400',
            'text' => 'text-cyan-600 dark:text-cyan-400',
            'trend-up' => 'text-cyan-400',
            'trend-down' => 'text-cyan-400',
        ],
        'orange' => [
            'border' => 'border-t-orange-500',
            'bg' => 'bg-orange-500/10',
            'icon-bg' => 'bg-orange-500/15',
            'icon-text' => 'text-orange-400',
            'text' => 'text-orange-600 dark:text-orange-400',
            'trend-up' => 'text-orange-400',
            'trend-down' => 'text-orange-400',
        ],
        'pink' => [
            'border' => 'border-t-pink-500',
            'bg' => 'bg-pink-500/10',
            'icon-bg' => 'bg-pink-500/15',
            'icon-text' => 'text-pink-400',
            'text' => 'text-pink-600 dark:text-pink-400',
            'trend-up' => 'text-pink-400',
            'trend-down' => 'text-pink-400',
        ],
        'zinc' => [
            'border' => 'border-t-zinc-400',
            'bg' => 'bg-zinc-500/10',
            'icon-bg' => 'bg-zinc-500/15',
            'icon-text' => 'text-zinc-400',
            'text' => 'text-zinc-600 dark:text-zinc-300',
            'trend-up' => 'text-zinc-400',
            'trend-down' => 'text-zinc-400',
        ],
    ];
    $a = $accentMap[$color] ?? $accentMap['zinc'];
@endphp

<div
    {{
        $attributes->class([
            'hp-dashboard-stat group relative rounded-xl border border-zinc-200 bg-white p-4 overflow-hidden',
            'border-t-[3px] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/5',
            'dark:border-zinc-700/80 dark:bg-zinc-800/80 dark:hover:border-zinc-600 dark:hover:shadow-black/20',
            $a['border'],
        ])
    }}
>
    {{-- Subtle glow background --}}
    <div
        class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100 {{ $a['bg'] }}"
    ></div>

    {{-- Label row --}}
    <div class="mb-3 flex items-center gap-2">
        @if ($icon)
            <div class="flex h-6 w-6 items-center justify-center rounded-md {{ $a['icon-bg'] }}">
                <flux:icon :name="$icon" class="h-3.5 w-3.5 {{ $a['icon-text'] }}" variant="mini" />
            </div>
        @endif
        <span
            class="text-[11px] font-semibold tracking-widest text-zinc-500 uppercase dark:text-zinc-400"
            >{{ $label }}</span
        >
    </div>

    {{-- Value --}}
    <div class="text-2xl font-black tabular-nums tracking-tight sm:text-3xl {{ $a['text'] }}">{{ $value }}</div>

    {{-- Description / Trend --}}
    @if ($description || $trend)
        <div class="mt-1.5 flex items-center gap-1.5">
            @if ($trend === 'up')
                <flux:icon name="arrow-trending-up" class="h-3.5 w-3.5 {{ $a['trend-up'] }}" variant="mini" />
            @elseif ($trend === 'down')
                <flux:icon name="arrow-trending-down" class="h-3.5 w-3.5 {{ $a['trend-down'] }}" variant="mini" />
            @endif
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                @if ($trendValue)
                    <strong class="{{ $trend === 'up' ? $a['trend-up'] : $a['trend-down'] }}">{{ $trendValue }}</strong>
                @endif
                {{ $description }}
            </span>
        </div>
    @endif
</div>
