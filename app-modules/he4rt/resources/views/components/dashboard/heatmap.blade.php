@props ([
    'variant' => 'week',
    'data',
    'title' => null,
    'subtitle' => null,
    'total' => null,
    'delta' => null,
    'icon' => null,
    'showLegend' => true,
    'showInsight' => true,
    'insightHeadline' => null,
    'insightDetail' => null,
    'highlightNow' => true,
    'legendLabels' => null,
    'accentColor' => 'violet',
    'rowLabels' => null,
    'compact' => false
])

@php
    $cellRamp = match ($accentColor) {
        'blue' => [
            null,
            'rgba(37,99,235,0.16)',
            'rgba(37,99,235,0.36)',
            'rgba(37,99,235,0.58)',
            'rgba(37,99,235,0.82)',
            'rgba(96,165,250,1)',
        ],
        'green' => [
            null,
            'rgba(16,185,129,0.16)',
            'rgba(16,185,129,0.36)',
            'rgba(16,185,129,0.58)',
            'rgba(16,185,129,0.82)',
            'rgba(52,211,153,1)',
        ],
        'amber' => [
            null,
            'rgba(245,158,11,0.16)',
            'rgba(245,158,11,0.36)',
            'rgba(245,158,11,0.58)',
            'rgba(245,158,11,0.82)',
            'rgba(251,191,36,1)',
        ],
        'red' => [
            null,
            'rgba(239,68,68,0.16)',
            'rgba(239,68,68,0.36)',
            'rgba(239,68,68,0.58)',
            'rgba(239,68,68,0.82)',
            'rgba(248,113,113,1)',
        ],
        default => [
            null,
            'rgba(124,58,237,0.16)',
            'rgba(124,58,237,0.36)',
            'rgba(124,58,237,0.58)',
            'rgba(124,58,237,0.82)',
            'rgba(168,85,247,1)',
        ],
    };

    $accentHex = match ($accentColor) {
        'blue' => '#3b82f6',
        'green' => '#10b981',
        'amber' => '#f59e0b',
        'red' => '#ef4444',
        default => '#7c3aed',
    };
    $accentBright = match ($accentColor) {
        'blue' => '#60a5fa',
        'green' => '#34d399',
        'amber' => '#fbbf24',
        'red' => '#f87171',
        default => '#a855f7',
    };

    $daysShort = $rowLabels ?? ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
    $daysFull = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado', 'Domingo'];

    $isWeek = $variant === 'week';

    // Dimensions
    if ($isWeek) {
        $cellW = $compact ? 16 : 22;
        $cellH = $compact ? 12 : 16;
        $gap = 3;
        $yLabelW = $compact ? 24 : 30;
        $xLabelH = 18;
        $cols = 24;
        $rows = 7;
        $gridW = $yLabelW + $cols * ($cellW + $gap);
        $gridH = $xLabelH + $rows * ($cellH + $gap);
        $xLabels = [0 => '00', 4 => '04', 8 => '08', 12 => '12', 16 => '16', 20 => '20'];
    } else {
        $cellS = 11;
        $cellW = $cellS;
        $cellH = $cellS;
        $gap = 3;
        $yLabelW = 28;
        $xLabelH = 18;
        $cols = 53;
        $rows = 7;
        $gridW = $yLabelW + $cols * ($cellS + $gap);
        $gridH = $xLabelH + $rows * ($cellS + $gap);
        $months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    }

    // Build data map + find max/peak
    $dataMap = [];
    $maxVal = 1;
    $peakRow = 0;
    $peakCol = 0;
    $peakVal = 0;
    $totalVal = 0;
    foreach ($data as $item) {
        $r = $item['row'] ?? ($item['day'] ?? 0);
        $c = $item['col'] ?? ($item['hour'] ?? ($item['week'] ?? 0));
        $v = $item['value'] ?? 0;
        $dataMap["{$r}-{$c}"] = $v;
        $totalVal += $v;
        if ($v > $maxVal) {
            $maxVal = $v;
        }
        if ($v > $peakVal) {
            $peakVal = $v;
            $peakRow = $r;
            $peakCol = $c;
        }
    }

    // Tier function
    $toTier = function (int $value) use ($maxVal): int {
        if ($value <= 0) {
            return 0;
        }
        $t = $value / $maxVal;
        if ($t < 0.2) {
            return 1;
        }
        if ($t < 0.4) {
            return 2;
        }
        if ($t < 0.6) {
            return 3;
        }
        if ($t < 0.85) {
            return 4;
        }
        return 5;
    };

    // Now indicator
    $nowRow = $highlightNow && $isWeek ? (int) now()->format('N') - 1 : -1;
    $nowCol = $highlightNow && $isWeek ? (int) now()->format('G') : -1;

    // Auto insight
    $autoHeadline = $insightHeadline;
    if (!$autoHeadline && $peakVal > 0) {
        if ($isWeek) {
            $autoHeadline =
                'Pico: ' .
                ($daysFull[$peakRow] ?? $peakRow) .
                ' as ' .
                str_pad((string) $peakCol, 2, '0', STR_PAD_LEFT) .
                'h';
        } else {
            $autoHeadline = 'Pico: semana ' . ($peakCol + 1) . ', ' . ($daysFull[$peakRow] ?? $peakRow);
        }
    }

    $displayTotal = $total ?? number_format($totalVal);
    $legendStops =
        $legendLabels ??
        ($isWeek ? ['0', '1-4', '5-9', '10-19', '20-34', '35+'] : ['0', '1-2', '3-5', '6-10', '11-18', '19+']);

    $componentId = 'heatmap-' . md5(serialize($data) . $variant);
@endphp

<div
    {{ $attributes->class('hp-dashboard-heatmap') }}
    x-data="{
        cell: null,
        pos: { x: 0, y: 0 },
        show(row, col, value, dayLabel, colLabel, e) {
            this.cell = { row, col, value, dayLabel, colLabel };
            this.pos = { x: e.clientX, y: e.clientY };
        },
        move(e) {
            if (this.cell) this.pos = { x: e.clientX, y: e.clientY };
        },
        hide() {
            this.cell = null;
        },
    }"
>
    {{-- Header --}}
    @if ($title)
        <div class="mb-4 flex items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-3">
                @if ($icon)
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                        style="background: {{ $accentHex }}20; color: {{ $accentBright }}"
                    >
                        <flux:icon :name="$icon" class="h-4 w-4" variant="mini" />
                    </div>
                @endif
                <div class="min-w-0">
                    <h3 class="text-[15px] leading-tight font-semibold dark:text-white">{{ $title }}</h3>
                    @if ($subtitle || $displayTotal || $delta)
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            @if ($displayTotal)
                                <span
                                    class="font-mono text-[13px] font-medium dark:text-white"
                                    >{{ $displayTotal }}</span
                                >
                            @endif
                            @if ($subtitle)
                                <span class="text-[13px] text-zinc-400"
                                    >{{ $displayTotal ? '· ' : '' }}{{ $subtitle }}</span
                                >
                            @endif
                            @if ($delta)
                                <span
                                    class="inline-flex items-center gap-0.5 font-mono text-[12px] font-medium {{ $delta['direction'] === 'up' ? 'text-emerald-500' : 'text-red-500' }}"
                                >
                                    <flux:icon
                                        :name="$delta['direction'] === 'up' ? 'arrow-trending-up' : 'arrow-trending-down'"
                                        class="h-3 w-3"
                                        variant="mini"
                                    />
                                    {{ $delta['value'] }}%
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            {{ $slot ?? '' }}
        </div>
    @endif

    {{-- Grid (SVG) --}}
    <div>
        <svg
            viewBox="0 0 {{ $gridW }} {{ $gridH }}"
            class="block w-full"
            style="max-height:{{ $gridH + 20 }}px"
            @mouseleave="hide()"
        >
            {{-- X axis labels --}}
            @if ($isWeek)
                @foreach ($xLabels as $h => $label)
                    <text
                        x="{{ $yLabelW + $h * ($cellW + $gap) + $cellW / 2 }}"
                        y="12"
                        text-anchor="middle"
                        class="fill-zinc-500 dark:fill-zinc-500"
                        style="font-size: 10px; font-family: 'JetBrains Mono', monospace; letter-spacing: 0.04em"
                        >{{ $label }}</text
                    >
                @endforeach
            @else
                @foreach ($months as $mi => $month)
                    <text
                        x="{{ $yLabelW + (int)floor(($mi / 12) * $cols) * ($cellW + $gap) }}"
                        y="12"
                        text-anchor="start"
                        class="fill-zinc-500 dark:fill-zinc-500"
                        style="font-size: 10px; font-family: 'JetBrains Mono', monospace; letter-spacing: 0.04em"
                        >{{ $month }}</text
                    >
                @endforeach
            @endif

            {{-- Y axis labels --}}
            @foreach ($daysShort as $di => $dayLabel)
                @if ($di % 2 === 0 || $isWeek)
                    <text
                        x="{{ $yLabelW - 8 }}"
                        y="{{ $xLabelH + $di * ($cellH + $gap) + $cellH / 2 + 4 }}"
                        text-anchor="end"
                        class="fill-zinc-500 dark:fill-zinc-500"
                        style="font-size: 10px; font-family: 'JetBrains Mono', monospace; letter-spacing: 0.04em"
                        >{{ $dayLabel }}</text
                    >
                @endif
            @endforeach

            {{-- Cells --}}
            @for ($r = 0; $r < $rows; $r++)
                @for ($c = 0; $c < $cols; $c++)
                    @php
                        $val = $dataMap["{$r}-{$c}"] ?? 0;
                        $tier = $toTier($val);
                        $fill = $tier === 0 ? 'transparent' : $cellRamp[$tier];
                        $x = $yLabelW + $c * ($cellW + $gap);
                        $y = $xLabelH + $r * ($cellH + $gap);
                        $isNow = $r === $nowRow && $c === $nowCol;
                        $dayLabel = $daysFull[$r] ?? $r;
                        $colLabel = $isWeek ? str_pad((string) $c, 2, '0', STR_PAD_LEFT) . 'h' : 'Sem ' . ($c + 1);
                    @endphp
                    <rect
                        x="{{ $x }}"
                        y="{{ $y }}"
                        width="{{ $cellW }}"
                        height="{{ $cellH }}"
                        rx="{{ $isWeek ? 3 : 2 }}"
                        fill="{{ $fill }}"
                        @if ($tier === 0) stroke="rgba(255,255,255,0.06)" stroke-width="1" @endif
                        style="cursor: default; transition: filter 120ms ease"
                        @mouseenter="show({{ $r }}, {{ $c }}, {{ $val }}, '{{ $dayLabel }}', '{{ $colLabel }}', $event)"
                        @mousemove="move($event)"
                    />
                    @if ($isNow)
                        <rect
                            x="{{ $x - 1.5 }}"
                            y="{{ $y - 1.5 }}"
                            width="{{ $cellW + 3 }}"
                            height="{{ $cellH + 3 }}"
                            rx="{{ $isWeek ? 4 : 3 }}"
                            fill="none"
                            stroke="{{ $accentBright }}"
                            stroke-width="1.5"
                            pointer-events="none"
                        />
                    @endif
                @endfor
            @endfor
        </svg>
    </div>

    {{-- Legend + Insight footer --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        {{-- Legend --}}
        @if ($showLegend)
            <div class="flex items-center gap-1.5">
                <span class="font-mono text-[10px] tracking-widest text-zinc-500 uppercase">menos</span>
                @foreach ($cellRamp as $i => $color)
                    <div
                        class="rounded-[3px]"
                        style="width:12px;height:12px;background:{{ $color ?? 'transparent' }};{{ $color ? '' : 'border:1px solid rgba(255,255,255,0.06)' }}"
                        title="{{ $legendStops[$i] ?? '' }}"
                    ></div>
                @endforeach
                <span class="font-mono text-[10px] tracking-widest text-zinc-500 uppercase">mais</span>
            </div>
        @endif

        @if (!$isWeek && $showLegend)
            <span class="font-mono text-[11px] text-zinc-500">Ultimos 12 meses</span>
        @endif
    </div>

    {{-- Insight bar --}}
    @if ($showInsight && $autoHeadline)
        <div
            class="mt-4 flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700/60 dark:bg-zinc-900/50"
        >
            <div
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md"
                style="background: {{ $accentHex }}20; color: {{ $accentBright }}"
            >
                <flux:icon name="bolt" class="h-3.5 w-3.5" variant="mini" />
            </div>
            <div class="min-w-0">
                <div class="text-[13px] font-medium dark:text-white">{{ $autoHeadline }}</div>
                @if ($insightDetail)
                    <div class="mt-0.5 text-[12px] text-zinc-400">{{ $insightDetail }}</div>
                @endif
            </div>
        </div>
    @endif

    {{-- Floating tooltip --}}
    <template x-if="cell">
        <div
            class="pointer-events-none fixed z-50 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 shadow-xl dark:border-zinc-600 dark:bg-zinc-800"
            :style="`left:${Math.min(pos.x + 12, window.innerWidth - 220)}px;top:${pos.y - 50}px;min-width:180px`"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <div
                class="font-mono text-[11px] font-medium tracking-widest text-zinc-400 uppercase"
                x-text="cell.dayLabel + ' · ' + cell.colLabel"
            ></div>
            <div
                class="mt-1 text-lg leading-none font-semibold"
                :class="cell.value > {{ (int)($maxVal * 0.7) }} ? 'text-violet-400' : 'text-white'"
            >
                <span x-text="cell.value"></span>
                <span
                    class="text-[13px] font-normal text-zinc-400"
                    x-text="cell.value === 1 ? ' caso' : ' casos'"
                ></span>
            </div>
        </div>
    </template>
</div>
