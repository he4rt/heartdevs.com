@php
    use He4rt\Moderation\Enums\AppealStatus;
    use He4rt\Moderation\Enums\ActionType;

    $statusColor = match ($appeal->status) {
        AppealStatus::Pending => 'zinc',
        AppealStatus::Reviewing => 'amber',
        AppealStatus::Upheld => 'red',
        AppealStatus::Overturned => 'green',
    };
    $isOverdue = $appeal->sla_deadline?->isPast() && !$appeal->status->isResolved();
    $case = $appeal->action?->case;
    $actionColorMap = [
        ActionType::Warn->value => 'amber',
        ActionType::Mute->value => 'zinc',
        ActionType::Kick->value => 'blue',
        ActionType::Ban->value => 'red',
        ActionType::Suspend->value => 'orange',
        ActionType::ContentRemove->value => 'purple',
    ];
@endphp

<div class="p-5 lg:p-7">
    {{-- Header --}}
    <div class="mb-6">
        <div class="mb-2 flex flex-wrap items-center gap-2.5">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ ucfirst($appeal->reason_category) }}
                <span class="font-normal text-zinc-400 dark:text-zinc-500">—</span>
                <span
                    class="font-medium text-zinc-600 dark:text-zinc-300"
                    >{{ '@' . ($appeal->appellant?->name ?? '?') }}</span
                >
            </h2>
            <flux:badge color="{{ $statusColor }}">{{ $appeal->status->getLabel() }}</flux:badge>
            @if ($appeal->action?->action_type)
                <flux:badge
                    color="{{ $actionColorMap[$appeal->action->action_type->value] ?? 'zinc' }}"
                    >{{ $appeal->action->action_type->getLabel() }}</flux:badge
                >
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400 dark:text-zinc-500">
            <span class="font-mono text-[11px]">{{ Str::limit($appeal->id, 8, '...') }}</span>
            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
            <span class="inline-flex items-center gap-1">
                <x-heroicon-o-calendar class="size-3" />
                {{ $appeal->created_at->diffForHumans() }}
            </span>
            <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
            <span
                @class ([
                    'inline-flex items-center gap-1',
                    'font-semibold text-red-500 dark:text-red-400' => $isOverdue
                ])
            >
                @if ($isOverdue)
                    <x-heroicon-o-exclamation-triangle class="size-3" />
                    {{
                        __(
                            'panel-admin::moderation.appeal_queue.detail.sla_overdue',
                        )
                    }}
                @else
                    <x-heroicon-o-clock class="size-3" />
                    {{ $appeal->sla_deadline?->diffForHumans() ?? '—' }}
                @endif
            </span>
        </div>
    </div>

    {{-- SLA overdue banner --}}
    @if ($isOverdue)
        <div
            class="mb-6 flex items-start gap-3 rounded-lg border border-red-300/40 bg-red-50/80 p-4 dark:border-red-500/20 dark:bg-red-500/5"
        >
            <div class="mt-0.5 rounded-md bg-red-400/20 p-1.5 dark:bg-red-400/10">
                <x-heroicon-o-exclamation-triangle class="size-4 text-red-600 dark:text-red-400" />
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                    {{
                        __(
                            'panel-admin::moderation.appeal_queue.detail.sla_overdue',
                        )
                    }}
                </p>
                <p class="mt-0.5 text-xs text-red-600/70 dark:text-red-400/60">
                    {{ __('panel-admin::moderation.appeal_queue.detail.sla_deadline') }}: {{ $appeal->sla_deadline->format('M d, Y H:i') }}
                </p>
            </div>
        </div>
    @endif

    {{-- Two-column grid --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_340px]">
        {{-- Left: appeal reason + original action + original case --}}
        <div class="flex flex-col gap-4">
            {{-- Appeal reason --}}
            <section class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]">
                <h3
                    class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                >
                    <x-heroicon-o-document-text class="size-3.5" />
                    {{
                        __(
                            'panel-admin::moderation.appeal_queue.detail.appeal',
                        )
                    }}
                </h3>
                <div class="mb-3 flex items-center gap-2">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400"
                        >{{ __('panel-admin::moderation.appeal_queue.detail.reason') }}:</span
                    >
                    <flux:badge size="sm">{{ ucfirst($appeal->reason_category) }}</flux:badge>
                </div>
                @if ($appeal->reason_text)
                    <div
                        class="rounded-md border border-zinc-100 bg-zinc-50 p-3.5 text-sm leading-relaxed text-zinc-800 dark:border-white/5 dark:bg-white/[0.03] dark:text-zinc-200"
                    >
                        {{ $appeal->reason_text }}
                    </div>
                @endif
            </section>

            {{-- Original action --}}
            @if ($appeal->action)
                <section
                    class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-bolt class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.appeal_queue.detail.original_action',
                            )
                        }}
                    </h3>
                    <div class="divide-y divide-zinc-100 dark:divide-white/5">
                        <div class="flex items-center justify-between py-2.5 first:pt-0">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{
                                __(
                                    'panel-admin::moderation.appeal_queue.detail.action_type',
                                )
                            }}</span>
                            <flux:badge
                                size="sm"
                                color="{{ $actionColorMap[$appeal->action->action_type->value] ?? 'zinc' }}"
                                >{{ $appeal->action->action_type->getLabel() }}</flux:badge
                            >
                        </div>
                        @if ($appeal->action->duration)
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{
                                    __(
                                        'panel-admin::moderation.appeal_queue.detail.action_duration',
                                    )
                                }}</span>
                                <span
                                    class="font-mono text-xs font-medium text-zinc-700 dark:text-zinc-300"
                                    >{{ $appeal->action->duration }}</span
                                >
                            </div>
                        @endif
                        @if ($appeal->action->moderator)
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{
                                    __(
                                        'panel-admin::moderation.appeal_queue.detail.action_moderator',
                                    )
                                }}</span>
                                <span
                                    class="font-mono text-xs text-zinc-700 dark:text-zinc-300"
                                    >{{ '@' . $appeal->action->moderator->name }}</span
                                >
                            </div>
                        @endif
                        @if ($appeal->action->reason)
                            <div class="py-2.5 last:pb-0">
                                <span class="mb-1 block text-xs text-zinc-500 dark:text-zinc-400">{{
                                    __(
                                        'panel-admin::moderation.appeal_queue.detail.action_reason',
                                    )
                                }}</span>
                                <p class="text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $appeal->action->reason }}</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Original case context --}}
            @if ($case)
                <section
                    class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-folder-open class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.appeal_queue.detail.original_case',
                            )
                        }}
                    </h3>

                    <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                        <span
                            class="font-mono text-[11px] text-zinc-400 dark:text-zinc-500"
                            >{{ Str::limit($case->id, 8, '...') }}</span
                        >
                        @if ($case->violation_type)
                            <flux:badge size="sm">{{ $case->violation_type->getLabel() }}</flux:badge>
                        @endif
                        @if ($case->severity)
                            @php
                                $severityColor = match ($case->severity->value) {
                                    'critical' => 'red',
                                    'high' => 'orange',
                                    'medium' => 'amber',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge
                                size="sm"
                                color="{{ $severityColor }}"
                                >{{ $case->severity->getLabel() }}</flux:badge
                            >
                        @endif
                        @if ($case->author)
                            <span
                                class="font-mono text-zinc-400 dark:text-zinc-500"
                                >{{ '@' . $case->author->name }}</span
                            >
                        @endif
                        @if ($case->source_platform)
                            <span class="inline-flex items-center gap-1 text-zinc-400 dark:text-zinc-500">
                                <x-heroicon-o-globe-alt class="size-3" />
                                {{ $case->source_platform->getLabel() }}
                            </span>
                        @endif
                    </div>

                    {{-- Content snapshot --}}
                    @if ($case->content_snapshot['text'] ?? null)
                        <div class="mb-4">
                            <span class="mb-1.5 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <x-heroicon-o-chat-bubble-bottom-center-text class="size-3" />
                                {{
                                    __(
                                        'panel-admin::moderation.appeal_queue.detail.content',
                                    )
                                }}
                            </span>
                            <div
                                class="rounded-md border border-zinc-100 bg-zinc-50 p-3 font-mono text-sm leading-relaxed text-zinc-800 dark:border-white/5 dark:bg-white/[0.03] dark:text-zinc-200"
                            >
                                {{ $case->content_snapshot['text'] }}
                            </div>
                        </div>
                    @endif

                    {{-- AI Scores --}}
                    @if ($case->ai_scores && count($case->ai_scores) > 0)
                        <div>
                            <span class="mb-2 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <x-heroicon-o-cpu-chip class="size-3" />
                                {{
                                    __(
                                        'panel-admin::moderation.appeal_queue.detail.ai_scores',
                                    )
                                }}
                            </span>
                            <div class="space-y-2">
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
                        </div>
                    @endif
                </section>
            @endif
        </div>

        {{-- Right: appellant + SLA + reviewer + actions --}}
        <div class="flex flex-col gap-4">
            {{-- Appellant --}}
            <section class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]">
                <h3
                    class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                >
                    <x-heroicon-o-user class="size-3.5" />
                    {{
                        __(
                            'panel-admin::moderation.appeal_queue.detail.appellant',
                        )
                    }}
                </h3>
                @if ($appeal->appellant)
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500 dark:bg-white/10 dark:text-zinc-400"
                        >
                            {{ strtoupper(mb_substr($appeal->appellant->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ '@' . $appeal->appellant->name }}</p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                {{ $appeal->appellant->created_at->format('M Y') }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">—</p>
                @endif
            </section>

            {{-- SLA --}}
            <section class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]">
                <h3
                    class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                >
                    <x-heroicon-o-clock class="size-3.5" />
                    {{
                        __(
                            'panel-admin::moderation.appeal_queue.detail.sla_deadline',
                        )
                    }}
                </h3>
                <div
                    @class ([
                        'rounded-md border px-3 py-2.5 text-center',
                        'border-red-200 bg-red-50/50 dark:border-red-500/20 dark:bg-red-500/5' => $isOverdue,
                        'border-zinc-100 bg-zinc-50/50 dark:border-white/5 dark:bg-white/[0.03]' => !$isOverdue
                    ])
                >
                    <div
                        @class ([
                            'text-sm font-semibold tabular-nums',
                            'text-red-600 dark:text-red-400' => $isOverdue,
                            'text-zinc-700 dark:text-zinc-300' => !$isOverdue
                        ])
                    >
                        {{ $appeal->sla_deadline?->format('M d, Y H:i') ?? '—' }}
                    </div>
                    @if ($appeal->sla_deadline && !$appeal->status->isResolved())
                        <div
                            @class ([
                                'mt-0.5 text-xs',
                                'text-red-500 dark:text-red-400' => $isOverdue,
                                'text-zinc-400 dark:text-zinc-500' => !$isOverdue
                            ])
                        >
                            {{ $appeal->sla_deadline->diffForHumans() }}
                        </div>
                    @endif
                </div>
            </section>

            {{-- Reviewer (if resolved) --}}
            @if ($appeal->status->isResolved() && $appeal->reviewer)
                <section
                    class="rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-check-badge class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.appeal_queue.detail.reviewer_notes',
                            )
                        }}
                    </h3>
                    <div class="mb-2 flex items-center gap-2 text-sm">
                        <span
                            class="font-medium text-zinc-700 dark:text-zinc-300"
                            >{{ '@' . $appeal->reviewer->name }}</span
                        >
                        <span
                            class="text-[11px] text-zinc-400 tabular-nums dark:text-zinc-500"
                            >{{ $appeal->resolved_at?->diffForHumans() }}</span
                        >
                    </div>
                    @if ($appeal->reviewer_notes)
                        <div
                            class="rounded-md border border-zinc-100 bg-zinc-50 p-3 text-sm leading-relaxed text-zinc-600 dark:border-white/5 dark:bg-white/[0.03] dark:text-zinc-400"
                        >
                            {{ $appeal->reviewer_notes }}
                        </div>
                    @endif
                </section>
            @endif

            {{-- Actions --}}
            @if (!$appeal->status->isResolved())
                <section
                    class="sticky top-4 rounded-lg border border-zinc-200/80 bg-white p-4 dark:border-white/5 dark:bg-white/[0.03]"
                >
                    <h3
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-wider text-zinc-400 uppercase dark:text-zinc-500"
                    >
                        <x-heroicon-o-scale class="size-3.5" />
                        {{
                            __(
                                'panel-admin::moderation.appeal_queue.detail.decision',
                            )
                        }}
                    </h3>
                    <div class="[&_.fi-ac-action]:w-full [&_.fi-ac-action]:justify-center flex flex-col gap-2">
                        {{ $this->overturnAction }} {{ $this->upholdAction }}
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
