@props ([
    'timeline',
    'isOwner' => false,
    'allowReplies' => true,
    'reactionCounts' => [],
    'reactionMine' => null,
])

<div
    class="flex items-center justify-between border-t border-gray-100 px-3 py-2 text-sm text-gray-500 sm:px-4 dark:border-white/5 dark:text-gray-400"
>
    <div class="flex items-center gap-3 sm:gap-4">
        @if ($allowReplies)
            <a
                href="{{ \He4rt\PanelApp\Pages\ThreadPage::getUrl(['record' => $timeline->id]) }}"
                class="hover:text-primary-500 flex items-center gap-1.5 transition"
            >
                <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                @if ($timeline->children_count > 0)
                    <span>{{ Number::abbreviate($timeline->children_count) }}</span>
                @endif
            </a>
        @else
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                @if ($timeline->children_count > 0)
                    <span>{{ Number::abbreviate($timeline->children_count) }}</span>
                @endif
            </span>
        @endif

        <livewire:timeline-reactions
            :timeline-id="$timeline->id"
            :counts="$reactionCounts ?? []"
            :mine="$reactionMine ?? null"
            :key="'rx-'.$timeline->id"
        />
    </div>

    @if ($isOwner && config('he4rt.features.timeline_pin'))
        <button
            type="button"
            wire:click="togglePin"
            class="flex shrink-0 items-center gap-1 text-xs transition-colors hover:text-amber-500 {{ $timeline->pinned ? 'text-amber-500' : '' }}"
            title="{{ $timeline->pinned ? 'Desafixar' : 'Fixar' }}"
        >
            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">{{ $timeline->pinned ? 'Desafixar' : 'Fixar' }}</span>
        </button>
    @endif
</div>
