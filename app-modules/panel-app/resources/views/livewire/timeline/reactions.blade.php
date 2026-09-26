@php
    use He4rt\Activity\Reaction\Enums\TimelineReaction;
@endphp

<div x-data="{ open: false }" class="relative" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        class="hover:text-primary-500 flex items-center gap-1.5 transition {{ $mine !== null ? 'text-primary-500' : '' }}"
    >
        @if ($mine !== null)
            <span class="text-sm leading-none">{{ TimelineReaction::from($mine)->getEmoji() }}</span>
        @else
            <x-heroicon-o-face-smile class="h-4 w-4" />
        @endif

        @if (array_sum($counts) > 0)
            <span>{{ Number::abbreviate(array_sum($counts)) }}</span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute bottom-full left-0 mb-2 flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-1.5 shadow-lg dark:border-white/10 dark:bg-gray-800"
    >
        @foreach ($reactions as $reaction)
            <button
                type="button"
                wire:click="reactWith('{{ $reaction->value }}')"
                @click="open = false"
                title="{{ $reaction->getLabel() }}"
                class="flex h-7 w-7 items-center justify-center rounded-full text-base transition hover:scale-125 {{ $mine === $reaction->value ? 'bg-primary-500/10' : '' }}"
            >
                {{ $reaction->getEmoji() }}
            </button>
        @endforeach
    </div>
</div>
