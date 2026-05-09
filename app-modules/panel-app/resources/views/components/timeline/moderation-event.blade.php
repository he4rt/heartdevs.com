@props (['timeline'])

@php
    $event = $timeline->postable;
    $moderatorVisible = $event->metadata['moderator_visible'] ?? false;
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
                    {{ $event->type->getLabel() }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-600"
                    >· {{ $event->occurred_at->diffForHumans(short: true) }}</span
                >
            </div>
            @if ($moderatorVisible && $event->moderator)
                <span class="text-xs text-gray-400 dark:text-gray-500"
                    >por {{ $event->moderator->display_name ?? $event->moderator->external_id }}</span
                >
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
            @if ($event->subject)
                <p class="text-lg font-bold text-gray-900 dark:text-white">
                    {{
                        $event->subject->display_name ??
                            $event->subject->external_id
                    }}
                </p>
            @endif
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">foi {{
                $event->type === \He4rt\Activity\Moderation\Enums\ModerationType::Ban
                    ? 'banido permanentemente'
                    : 'removido'
            }} da comunidade</p>
        </div>

        @if ($event->reason)
            <div
                class="border-danger-100 bg-danger-50/50 dark:border-danger-500/10 dark:bg-danger-500/5 border-t px-5 py-3"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-danger-600 dark:text-danger-400 font-semibold">Motivo:</span> {{ $event->reason }}
                </p>
            </div>
        @endif
    </div>
</div>
