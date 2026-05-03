@props ([
    'data', // array[7][24] of int (0-5 intensity), Mon=0..Sun=6
    'labels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'],
    'accentColor' => 'violet' // tailwind color name
])

@php
    $intensityMap = match ($accentColor) {
        'violet' => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-violet-500/20',
            'bg-violet-500/35',
            'bg-violet-500/50',
            'bg-violet-500/70',
            'bg-violet-500/90',
        ],
        'blue' => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-blue-500/20',
            'bg-blue-500/35',
            'bg-blue-500/50',
            'bg-blue-500/70',
            'bg-blue-500/90',
        ],
        'green' => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-emerald-500/20',
            'bg-emerald-500/35',
            'bg-emerald-500/50',
            'bg-emerald-500/70',
            'bg-emerald-500/90',
        ],
        'amber' => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-amber-500/20',
            'bg-amber-500/35',
            'bg-amber-500/50',
            'bg-amber-500/70',
            'bg-amber-500/90',
        ],
        'red' => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-red-500/20',
            'bg-red-500/35',
            'bg-red-500/50',
            'bg-red-500/70',
            'bg-red-500/90',
        ],
        default => [
            'bg-zinc-100 dark:bg-zinc-700/50',
            'bg-violet-500/20',
            'bg-violet-500/35',
            'bg-violet-500/50',
            'bg-violet-500/70',
            'bg-violet-500/90',
        ],
    };
@endphp

<div {{ $attributes->class('hp-dashboard-heatmap') }}>
    <div class="flex gap-1">
        {{-- Day labels --}}
        <div class="flex flex-col gap-0.5 pt-5">
            @foreach ($labels as $label)
                <div class="h-4 pr-1.5 text-right font-mono text-[10px] leading-4 text-zinc-400">{{ $label }}</div>
            @endforeach
        </div>

        {{-- Grid --}}
        <div class="flex-1">
            {{-- Hour headers --}}
            <div class="mb-1 flex gap-0.5">
                @for ($h = 0; $h < 24; $h++)
                    <div class="w-4 text-center font-mono text-[9px] leading-3 text-zinc-500">
                        {{ $h % 3 === 0 ? $h : '' }}
                    </div>
                @endfor
            </div>

            {{-- Cells --}}
            @foreach ($data as $row)
                <div class="mb-0.5 flex gap-0.5">
                    @foreach ($row as $v)
                        <div
                            class="h-4 w-4 rounded-sm transition-transform hover:z-10 hover:scale-150 {{ $intensityMap[min($v, 5)] }}"
                            title="Intensidade: {{ $v }}"
                        ></div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Legend --}}
    <div class="mt-2 flex items-center justify-end gap-1.5">
        <span class="font-mono text-[10px] text-zinc-400">menos</span>
        @foreach ($intensityMap as $cls)
            <div class="h-3 w-3 rounded-sm {{ $cls }}"></div>
        @endforeach
        <span class="font-mono text-[10px] text-zinc-400">mais</span>
    </div>
</div>
