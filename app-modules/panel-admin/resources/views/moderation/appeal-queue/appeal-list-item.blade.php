@php
    use He4rt\Moderation\Enums\AppealStatus;
    use He4rt\Moderation\Enums\ActionType;

    $isSelected = $this->selectedAppealId === $appeal->id;
    $statusColor = match ($appeal->status) {
        AppealStatus::Pending => 'zinc',
        AppealStatus::Reviewing => 'amber',
        AppealStatus::Upheld => 'red',
        AppealStatus::Overturned => 'green',
    };
    $isOverdue = $appeal->sla_deadline?->isPast() && !$appeal->status->isResolved();
    $actionColorMap = [
        ActionType::Warn->value => 'amber',
        ActionType::Mute->value => 'zinc',
        ActionType::Kick->value => 'blue',
        ActionType::Ban->value => 'red',
        ActionType::Suspend->value => 'orange',
        ActionType::ContentRemove->value => 'purple',
    ];
@endphp

<div
    wire:click="selectAppeal('{{ $appeal->id }}')"
    wire:key="appeal-{{ $appeal->id }}"
    @class ([
        'group relative cursor-pointer border-b px-5 py-3.5 transition-all duration-150',
        'border-zinc-200/60 dark:border-white/5',
        'border-l-2 border-l-primary-500 bg-primary-50/80 dark:border-l-primary-400 dark:bg-primary-500/10' => $isSelected,
        'border-l-2 border-l-transparent hover:bg-zinc-50 dark:hover:bg-white/[0.03]' => !$isSelected
    ])
>
    {{-- Row 1: status + action type badge + SLA --}}
    <div class="mb-1.5 flex items-center gap-2">
        <flux:badge size="sm" color="{{ $statusColor }}">{{ $appeal->status->getLabel() }}</flux:badge>

        @if ($appeal->action?->action_type)
            <flux:badge
                size="sm"
                color="{{ $actionColorMap[$appeal->action->action_type->value] ?? 'zinc' }}"
                >{{ $appeal->action->action_type->getLabel() }}</flux:badge
            >
        @endif

        <span
            @class ([
                'ml-auto inline-flex items-center gap-1 text-[11px] tabular-nums',
                'font-semibold text-red-500 dark:text-red-400' => $isOverdue,
                'text-zinc-400 dark:text-zinc-500' => !$isOverdue
            ])
        >
            @if ($isOverdue)
                <x-heroicon-o-exclamation-triangle class="size-3" />
            @else
                <x-heroicon-o-clock class="size-3" />
            @endif
            {{
                $appeal->sla_deadline?->diffForHumans(short: true) ??
                    '—'
            }}
        </span>
    </div>

    {{-- Row 2: reason category + appellant --}}
    <div class="mb-1.5 flex items-center gap-2">
        <span
            @class ([
                'text-sm font-semibold',
                'text-zinc-900 dark:text-zinc-100' => $isSelected,
                'text-zinc-700 dark:text-zinc-300' => !$isSelected
            ])
        >
            {{ ucfirst($appeal->reason_category) }}
        </span>
        <span class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500">
            {{ '@' . ($appeal->appellant?->name ?? '?') }}
        </span>
    </div>

    {{-- Row 3: reason text preview --}}
    @if ($appeal->reason_text)
        <p
            @class ([
                'mb-2 line-clamp-2 text-[13px] leading-relaxed',
                'text-zinc-700 dark:text-zinc-300' => $isSelected,
                'text-zinc-500 dark:text-zinc-400' => !$isSelected
            ])
        >
            {{ $appeal->reason_text }}
        </p>
    @endif

    {{-- Row 4: meta --}}
    <div class="flex items-center gap-2.5 text-[11px]">
        <span class="inline-flex items-center gap-1 text-zinc-400 dark:text-zinc-500">
            <x-heroicon-o-calendar class="size-3" />
            {{ $appeal->created_at->diffForHumans(short: true) }}
        </span>

        @if ($appeal->action?->case?->violation_type)
            <span class="inline-flex items-center gap-1 text-zinc-400 dark:text-zinc-500">
                <x-heroicon-o-shield-exclamation class="size-3" />
                {{ $appeal->action->case->violation_type->getLabel() }}
            </span>
        @endif

        @if ($appeal->action?->case?->source_platform)
            <span class="inline-flex items-center gap-1 text-zinc-400 dark:text-zinc-500">
                <x-heroicon-o-globe-alt class="size-3" />
                {{ $appeal->action->case->source_platform->getLabel() }}
            </span>
        @endif
    </div>
</div>
