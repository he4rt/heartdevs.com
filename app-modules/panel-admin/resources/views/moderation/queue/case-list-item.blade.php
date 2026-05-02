@php
    use He4rt\Moderation\Enums\CaseStatus;

    $isSelected = $this->selectedCaseId === $case->id;
    $topScore = $case->ai_scores ? max(array_values($case->ai_scores)) : 0;
    $statusColor = match ($case->status) {
        CaseStatus::Pending => 'zinc',
        CaseStatus::Assigned => 'blue',
        CaseStatus::Resolved => 'green',
        CaseStatus::Escalated => 'red',
        CaseStatus::Dismissed => 'amber',
    };
@endphp

<div
    wire:click="selectCase('{{ $case->id }}')"
    wire:key="case-{{ $case->id }}"
    @class ([
        'group relative cursor-pointer border-b px-3.5 py-3 transition-all duration-150 sm:px-5 sm:py-3.5',
        'border-zinc-200/60 dark:border-white/5',
        'border-l-2 border-l-primary-500 bg-primary-50/80 dark:border-l-primary-400 dark:bg-primary-500/10' => $isSelected,
        'border-l-2 border-l-transparent hover:bg-zinc-50 dark:hover:bg-white/[0.03]' => !$isSelected
    ])
>
    {{-- Row 1: priority + violation + status + time --}}
    <div class="mb-1.5 flex items-center gap-1.5 sm:gap-2">
        <span
            @class ([
                'inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded px-1 text-[10px] font-bold tabular-nums',
                'bg-red-500/15 text-red-600 dark:bg-red-400/15 dark:text-red-400' => $case->priority >= 90,
                'bg-amber-500/15 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400' =>
                    $case->priority >= 70 && $case->priority < 90,
                'bg-zinc-500/10 text-zinc-500 dark:bg-zinc-400/10 dark:text-zinc-400' => $case->priority < 70
            ])
            >{{ $case->priority }}</span
        >

        <span
            @class ([
                'truncate text-sm font-semibold',
                'text-zinc-900 dark:text-zinc-100' => $isSelected,
                'text-zinc-700 dark:text-zinc-300' => !$isSelected
            ])
        >
            {{ $case->violation_type?->getLabel() ?? '—' }}
        </span>

        <flux:badge size="sm" color="{{ $statusColor }}" class="shrink-0">{{ $case->status->getLabel() }}</flux:badge>

        <span
            @class ([
                'ml-auto shrink-0 text-[11px] tabular-nums',
                'text-zinc-500 dark:text-zinc-400' => $isSelected,
                'text-zinc-400 dark:text-zinc-500' => !$isSelected
            ])
        >
            {{ $case->created_at->diffForHumans(short: true) }}
        </span>
    </div>

    {{-- Row 2: content preview --}}
    <p
        @class ([
            'mb-2 line-clamp-2 text-[13px] leading-relaxed',
            'text-zinc-700 dark:text-zinc-300' => $isSelected,
            'text-zinc-500 dark:text-zinc-400' => !$isSelected
        ])
    >
        {{ $case->content_snapshot['text'] ?? '' }}
    </p>

    {{-- Row 3: meta --}}
    <div class="flex items-center gap-2 text-[11px] sm:gap-2.5">
        <span class="inline-flex shrink-0 items-center gap-1 text-zinc-400 dark:text-zinc-500">
            <x-heroicon-o-globe-alt class="size-3" />
            <span class="hidden sm:inline">{{ $case->source_platform->getLabel() }}</span>
        </span>

        <span class="min-w-0 truncate font-mono text-zinc-400 dark:text-zinc-500">
            {{ '@' . ($case->author?->name ?? '?') }}
        </span>

        <span class="ml-auto flex shrink-0 items-center gap-2">
            @if ($case->reports->count())
                <span class="inline-flex items-center gap-1 text-zinc-400 dark:text-zinc-500">
                    <x-heroicon-o-flag class="size-3" />
                    {{ $case->reports->count() }}
                </span>
            @endif

            @if ($topScore > 0)
                <span
                    @class ([
                        'inline-flex items-center rounded px-1.5 py-0.5 font-mono text-[10px] font-bold tabular-nums',
                        'bg-red-500/15 text-red-600 dark:bg-red-400/15 dark:text-red-400' => $topScore >= 0.7,
                        'bg-amber-500/15 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400' =>
                            $topScore >= 0.4 && $topScore < 0.7,
                        'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400' => $topScore < 0.4
                    ])
                    >{{ number_format($topScore, 2) }}</span
                >
            @endif
        </span>
    </div>
</div>
