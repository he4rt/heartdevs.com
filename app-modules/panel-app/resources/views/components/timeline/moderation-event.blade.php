@props (['timeline'])

@php
    $postable = $timeline->postable;
    $isAction = $timeline->postable_type === 'moderation_action';

    if ($isAction) {
        $actionLabel = $postable->action_type->getLabel();
        $isBan = $postable->action_type === \He4rt\Moderation\Enums\ActionType::Ban;
        $reason = $postable->reason;
        $moderatorName = $postable->moderator?->name;
        $moderatorVisible = $postable->metadata['moderator_visible'] ?? true;
        $subjectName = $postable->case?->author?->name ?? $postable->case?->author?->username;
        $timestamp = $postable->created_at;
        $reportsCount = $postable->case?->reports()?->count() ?? 0;
        $violationType = $postable->case?->violation_type;
    } else {
        $actionLabel = $postable->type->getLabel();
        $isBan = $postable->type === \He4rt\Activity\Moderation\Enums\ModerationType::Ban;
        $reason = $postable->reason;
        $moderatorName = $postable->moderator?->display_name ?? $postable->moderator?->external_id;
        $moderatorVisible = $postable->metadata['moderator_visible'] ?? false;
        $subjectName = $postable->subject?->display_name ?? $postable->subject?->external_id;
        $timestamp = $postable->occurred_at;
        $reportsCount = 0;
        $violationType = null;
    }
@endphp

<div
    class="border-danger-300 ring-danger-500/10 dark:border-danger-500/30 overflow-hidden rounded-xl border bg-white shadow-sm ring-1 dark:bg-white/5"
>
    <div class="from-danger-500 to-danger-400 h-1 bg-gradient-to-r"></div>

    <div class="flex items-center gap-3 px-5 pt-4 pb-2">
        <div
            class="bg-danger-100 ring-danger-500/20 dark:bg-danger-500/15 flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-2"
        >
            <x-heroicon-s-shield-exclamation class="text-danger-500 h-5 w-5" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">Moderação</span>
                <span
                    class="bg-danger-500 rounded-full px-2 py-0.5 text-[9px] font-bold tracking-wider text-white uppercase"
                >
                    {{ $actionLabel }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-600"
                    >· {{ $timestamp->diffForHumans(short: true) }}</span
                >
            </div>
            @if ($moderatorVisible && $moderatorName)
                <span class="text-xs text-gray-400 dark:text-gray-500">por {{ $moderatorName }}</span>
            @endif
        </div>
    </div>

    <div
        class="border-danger-200 from-danger-50 to-danger-50/50 dark:border-danger-500/20 dark:from-danger-950/40 dark:to-danger-950/20 mx-5 mb-4 overflow-hidden rounded-xl border bg-gradient-to-br via-white dark:via-zinc-900"
    >
        <div class="px-5 py-5 text-center">
            <div
                class="bg-danger-100 dark:bg-danger-500/15 mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full"
            >
                <x-heroicon-s-no-symbol class="text-danger-500 h-7 w-7" />
            </div>
            @if ($subjectName)
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $subjectName }}</p>
            @endif
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">foi {{ $isBan ? 'banido permanentemente' : 'removido' }} da comunidade</p>
        </div>

        @if ($reason)
            <div
                class="border-danger-100 bg-danger-50/50 dark:border-danger-500/10 dark:bg-danger-500/5 border-t px-5 py-3"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-danger-600 dark:text-danger-400 font-semibold">Motivo:</span> {{ $reason }}
                </p>
            </div>
        @endif

        @if ($reportsCount > 0 || $violationType)
            <div
                class="border-danger-100 dark:border-danger-500/10 flex items-center justify-center gap-5 border-t px-5 py-2.5 text-xs text-gray-400 dark:text-gray-500"
            >
                @if ($reportsCount > 0)
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-o-flag class="text-danger-400 h-3.5 w-3.5" />
                        {{ $reportsCount }} {{ str('denúncia')->plural($reportsCount) }}
                    </span>
                @endif
                @if ($violationType)
                    <span class="flex items-center gap-1.5">
                        <x-heroicon-o-exclamation-triangle class="text-warning-500 h-3.5 w-3.5" />
                        {{
                            $violationType instanceof \BackedEnum
                                ? $violationType->getLabel()
                                : $violationType
                        }}
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>
