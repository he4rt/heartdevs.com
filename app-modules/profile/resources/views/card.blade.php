<a
    href="{{ $card->url }}"
    class="block w-full rounded-xl border border-gray-200 bg-white p-4 shadow-lg transition hover:border-purple-300 dark:border-white/10 dark:bg-gray-900 dark:hover:border-purple-500/40"
>
    <div class="flex items-start gap-3">
        @if ($card->avatarUrl)
            <img
                src="{{ $card->avatarUrl }}"
                alt="{{ $card->name }}"
                class="h-12 w-12 shrink-0 rounded-full object-cover"
            />
        @else
            <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-sm font-semibold text-white"
            >
                {{ $card->initials }}
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">{{ $card->name }}</p>
                @if ($card->level)
                    <span
                        class="shrink-0 rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-bold text-purple-700 dark:bg-purple-500/10 dark:text-purple-300"
                    >
                        LVL {{ $card->level }}
                    </span>
                @endif
            </div>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ '@' . $card->username }}</p>
        </div>
    </div>

    @if ($card->role)
        <p class="mt-3 line-clamp-2 text-sm text-gray-700 dark:text-gray-300">{{ $card->role }}</p>
    @endif

    @if ($card->location)
        <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
            <x-heroicon-m-map-pin class="h-3 w-3 shrink-0" />
            <span class="truncate">{{ $card->location }}</span>
        </p>
    @endif

    @if ($card->skills !== [])
        <div class="mt-3 flex flex-wrap items-center gap-1.5">
            @foreach ($card->skills as $skill)
                <span
                    class="rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/10 dark:text-purple-300"
                >
                    {{ $skill }}
                </span>
            @endforeach
            @if ($card->remainingSkills > 0)
                <span class="text-xs text-gray-400 dark:text-gray-500">+{{ $card->remainingSkills }}</span>
            @endif
        </div>
    @endif

    @if ($card->availableForProposals)
        <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-green-600 dark:text-green-400">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-green-500"></span>
            Aberto a propostas
        </p>
    @endif
</a>
