@php
    use He4rt\Moderation\Enums\CaseStatus;
    use He4rt\Moderation\Enums\ActionType;

    $priorityColor = $case->priority >= 90 ? 'red' : ($case->priority >= 70 ? 'amber' : 'zinc');
    $statusColor = match ($case->status) {
        CaseStatus::Pending => 'zinc',
        CaseStatus::Assigned => 'blue',
        CaseStatus::Resolved => 'green',
        CaseStatus::Escalated => 'red',
        CaseStatus::Dismissed => 'amber',
    };
    $actionColorMap = [
        ActionType::Warn->value => 'amber',
        ActionType::Mute->value => 'zinc',
        ActionType::Kick->value => 'blue',
        ActionType::Ban->value => 'red',
        ActionType::Suspend->value => 'orange',
        ActionType::ContentRemove->value => 'purple',
    ];
@endphp

<div class="p-4 sm:p-5 lg:p-7">
    {{-- Header --}}
    <div class="mb-4 sm:mb-6">
        <div class="mb-2 flex flex-wrap items-center gap-1.5 sm:gap-2.5">
            <h2 class="w-full text-base font-semibold text-zinc-900 sm:w-auto sm:text-lg dark:text-zinc-100">
                {{ $case->violation_type?->getLabel() ?? '—' }}
                <span class="font-normal text-zinc-400 dark:text-zinc-500">—</span>
                <span
                    class="font-medium text-zinc-600 dark:text-zinc-300"
                    >{{ '@' . Str::limit($case->author?->name ?? '?', 20) }}</span
                >
            </h2>
            <span
                @class ([
                    'inline-flex h-5 min-w-5 items-center justify-center rounded px-1.5 text-[10px] font-bold tabular-nums',
                    'bg-red-500/15 text-red-600 dark:bg-red-400/15 dark:text-red-400' => $case->priority >= 90,
                    'bg-amber-500/15 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400' =>
                        $case->priority >= 70 && $case->priority < 90,
                    'bg-zinc-500/10 text-zinc-500 dark:bg-zinc-400/10 dark:text-zinc-400' => $case->priority < 70
                ])
                >{{ $case->priority }}</span
            >
            <flux:badge color="{{ $statusColor }}">{{ $case->status->getLabel() }}</flux:badge>
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400 dark:text-zinc-500">
            <span class="font-mono text-[11px]">{{ Str::limit($case->id, 8, '...') }}</span>
            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
            <span class="inline-flex items-center gap-1">
                <x-dynamic-component
                    :component="'heroicon-o-' . $case->source_platform->getIcon()->value"
                    class="size-3.5"
                />
                {{ $case->source_platform->getLabel() }}
            </span>
            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
            <span>{{ $case->created_at->diffForHumans() }}</span>
            @if ($case->reports->count())
                <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                <span class="inline-flex items-center gap-1">
                    <x-heroicon-o-flag class="size-3" />
                    {{ $case->reports->count() }} report{{ $case->reports->count() !== 1 ? 's' : '' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Suggested action banner --}}
    @if ($case->suggested_action && $case->status === CaseStatus::Pending)
        <div
            class="mb-4 flex items-start gap-2.5 rounded-lg border border-amber-300/40 bg-amber-50/80 p-3 sm:mb-6 sm:gap-3 sm:p-4 dark:border-amber-500/20 dark:bg-amber-500/5"
        >
            <div class="mt-0.5 rounded-md bg-amber-400/20 p-1.5 dark:bg-amber-400/10">
                <x-heroicon-o-light-bulb class="size-4 text-amber-600 dark:text-amber-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    {{
                        __('panel-admin::moderation.queue.detail.suggested_action', [
                            'action' => $case->suggested_action->getLabel(),
                        ])
                    }}
                </p>
                <p class="mt-0.5 text-xs text-amber-700/70 dark:text-amber-400/60">
                    @php
                        $priorOffenses = $case->author
                            ? \He4rt\Moderation\Enforcement\ModerationAction::query()
                                ->whereHas('case', fn($q) => $q->where('author_id', $case->author_id))
                                ->where('created_at', '>=', now()->subDays(30))
                                ->count()
                            : 0;
                    @endphp
                    @if ($priorOffenses > 0)
                        {{
                            __('panel-admin::moderation.queue.detail.prior_offenses', [
                                'count' => $priorOffenses,
                            ])
                        }}
                    @else
                        {{ __('panel-admin::moderation.queue.detail.no_history') }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- Two-column grid --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_340px]">
        {{-- Left: content + scores + reports --}}
        <div class="flex flex-col gap-4">
            {{-- Content snapshot --}}
            <section
                class="rounded-lg border border-zinc-200/80 bg-white p-3 sm:p-4 dark:border-white/5 dark:bg-white/[0.03]"
            >
                <h3
                    class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                >
                    <x-heroicon-o-chat-bubble-bottom-center-text class="size-3.5" />
                    {{ __('panel-admin::moderation.queue.detail.content') }}
                </h3>
                <div
                    class="rounded-md border border-zinc-100 bg-zinc-50 p-3 font-mono text-xs leading-relaxed break-words text-zinc-800 sm:p-3.5 sm:text-sm dark:border-white/5 dark:bg-white/[0.03] dark:text-zinc-200"
                >
                    {{ $case->content_snapshot['text'] ?? '—' }}
                </div>
            </section>

            {{-- AI Scores --}}
            @if ($case->ai_scores && count($case->ai_scores) > 0)
                <section
                    class="rounded-lg border border-zinc-200/80 bg-white p-3 sm:p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-cpu-chip class="size-3.5" />
                        {{ __('panel-admin::moderation.queue.detail.ai_scores') }}
                    </h3>
                    <div class="space-y-2.5">
                        @foreach ($case->ai_scores as $category => $score)
                            @php
                                $barColor =
                                    $score >= 0.7
                                        ? 'bg-red-500 dark:bg-red-400'
                                        : ($score >= 0.4
                                            ? 'bg-amber-500 dark:bg-amber-400'
                                            : 'bg-emerald-500 dark:bg-emerald-400');
                                $textColor =
                                    $score >= 0.7
                                        ? 'text-red-600 dark:text-red-400'
                                        : ($score >= 0.4
                                            ? 'text-amber-600 dark:text-amber-400'
                                            : 'text-emerald-600 dark:text-emerald-400');
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span
                                        class="text-xs text-zinc-500 dark:text-zinc-400"
                                        >{{ ucfirst($category) }}</span
                                    >
                                    <span
                                        class="font-mono text-xs font-bold tabular-nums {{ $textColor }}"
                                        >{{ number_format($score, 2) }}</span
                                    >
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/5">
                                    <div
                                        class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                                        style="width: {{ $score * 100 }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Reports --}}
            @if ($case->reports->isNotEmpty())
                <section
                    class="rounded-lg border border-zinc-200/80 bg-white p-3 sm:p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-flag class="size-3.5" />
                        {{ __('panel-admin::moderation.queue.detail.reports') }}
                        <span
                            class="rounded-full bg-zinc-100 px-1.5 py-0.5 text-[10px] font-bold text-zinc-500 tabular-nums dark:bg-white/10 dark:text-zinc-400"
                            >{{ $case->reports->count() }}</span
                        >
                    </h3>
                    <div class="divide-y divide-zinc-100 dark:divide-white/5">
                        @foreach ($case->reports as $report)
                            <div class="py-2.5 first:pt-0 last:pb-0">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ '@' . ($report->reporter?->name ?? '?') }}
                                    </span>
                                    <flux:badge size="sm">{{ $report->reason->getLabel() }}</flux:badge>
                                </div>
                                @if ($report->details)
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $report->details }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Right: user + actions --}}
        <div class="flex flex-col gap-4">
            {{-- User history --}}
            <section
                class="rounded-lg border border-zinc-200/80 bg-white p-3 sm:p-4 dark:border-white/5 dark:bg-white/[0.03]"
            >
                <h3
                    class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                >
                    <x-heroicon-o-user class="size-3.5" />
                    {{ __('panel-admin::moderation.queue.detail.author') }}
                </h3>
                @if ($case->author)
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500 dark:bg-white/10 dark:text-zinc-400"
                        >
                            {{ strtoupper(mb_substr($case->author->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ '@' . $case->author->name }}</p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                {{
                                    __('panel-admin::moderation.queue.detail.member_since', [
                                        'date' => $case->author->created_at->format('M Y'),
                                    ])
                                }}
                            </p>
                        </div>
                    </div>
                    @php
                        $pastActions = \He4rt\Moderation\Enforcement\ModerationAction::query()
                            ->whereHas('case', fn($q) => $q->where('author_id', $case->author_id))
                            ->with('case')
                            ->latest('created_at')
                            ->limit(10)
                            ->get();
                    @endphp
                    <div
                        class="mb-3 rounded-md border border-zinc-100 bg-zinc-50/50 px-3 py-2.5 text-center dark:border-white/5 dark:bg-white/[0.03]"
                    >
                        <div
                            @class ([
                                'text-xl font-bold tabular-nums',
                                'text-red-500 dark:text-red-400' => $pastActions->count() > 0,
                                'text-emerald-500 dark:text-emerald-400' => $pastActions->count() === 0
                            ])
                            >{{ $pastActions->count() }}
                        </div>
                        <div class="text-[10px] font-medium tracking-wider text-zinc-400 uppercase dark:text-zinc-500">
                            {{ __('panel-admin::moderation.queue.detail.infractions') }}
                        </div>
                    </div>
                    @if ($pastActions->isNotEmpty())
                        <div class="space-y-2">
                            <h4
                                class="text-[10px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                            >
                                {{
                                    __(
                                        'panel-admin::moderation.queue.detail.past_actions',
                                    )
                                }}
                            </h4>
                            @foreach ($pastActions->take(5) as $pastAction)
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <flux:badge
                                        size="sm"
                                        color="{{ $actionColorMap[$pastAction->action_type->value] ?? 'zinc' }}"
                                        >{{ $pastAction->action_type->getLabel() }}</flux:badge
                                    >
                                    <span
                                        class="text-[11px] text-zinc-400 tabular-nums dark:text-zinc-500"
                                        >{{ $pastAction->created_at->diffForHumans(short: true) }}</span
                                    >
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('panel-admin::moderation.queue.detail.no_history') }}</p>
                    @endif
                @else
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">—</p>
                @endif
            </section>

            {{-- Action buttons --}}
            @if ($case->status->isOpen())
                <section
                    class="sticky top-4 rounded-lg border border-zinc-200/80 bg-white p-3 sm:p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-bolt class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.queue.detail.actions_heading',
                            )
                        }}
                    </h3>
                    <div class="[&_.fi-ac-action]:w-full [&_.fi-ac-action]:justify-center flex flex-col gap-2">
                        {{ $this->takeActionAction }} {{ $this->escalateAction }} {{ $this->dismissAction }}
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
