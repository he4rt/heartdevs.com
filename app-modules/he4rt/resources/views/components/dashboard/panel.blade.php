@props ([
    'title' => null,
    'badge' => null,
    'footerText' => null,
    'compact' => false
])

<div
    {{
        $attributes->class([
            'hp-dashboard-panel rounded-xl border border-zinc-200 bg-white overflow-hidden',
            'transition-colors hover:border-violet-300',
            'dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-violet-600',
        ])
    }}
>
    @if ($title)
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
            <h3 class="text-sm font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                {{ $title }}
            </h3>
            @if ($badge)
                <flux:badge size="sm">{{ $badge }}</flux:badge>
            @endif
        </div>
    @endif

    <div @class (['px-5', 'py-4' => !$compact, 'pt-4' => $compact])> {{ $slot }}</div>

    @if ($footerText)
        <div
            class="border-t border-zinc-200 px-5 py-2.5 text-xs text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900/30"
        >
            {!! $footerText !!}
        </div>
    @endif
</div>
