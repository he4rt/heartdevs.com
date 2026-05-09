@props (['timeline', 'isOwner' => false])

<div
    class="flex items-center justify-between border-t border-gray-100 px-4 py-2 text-sm text-gray-500 dark:border-white/5 dark:text-gray-400"
>
    <div class="flex items-center gap-4">
        <span class="flex items-center gap-1.5">
            <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
            @if ($timeline->children_count > 0)
                <span>{{ Number::abbreviate($timeline->children_count) }}</span>
            @endif
        </span>

        <span class="flex items-center gap-1.5">
            <x-heroicon-o-face-smile class="h-4 w-4" />
            @if ($timeline->reactions_count > 0)
                <span>{{ Number::abbreviate($timeline->reactions_count) }}</span>
            @endif
        </span>

        <span
            class="flex cursor-help items-center gap-1.5"
            title="{{ Number::format($timeline->views) }} {{ str('visualização')->plural($timeline->views) }}"
        >
            <x-heroicon-o-eye class="h-4 w-4" />
            @if ($timeline->views > 0)
                <span>{{ Number::abbreviate($timeline->views) }}</span>
            @endif
        </span>
    </div>

    @if ($isOwner)
        <button
            wire:click="togglePin"
            class="flex items-center gap-1 text-xs transition-colors hover:text-amber-500 {{ $timeline->pinned ? 'text-amber-500' : '' }}"
        >
            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
            {{ $timeline->pinned ? 'Desafixar' : 'Fixar' }}
        </button>
    @endif
</div>
