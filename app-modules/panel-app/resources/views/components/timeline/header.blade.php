@props (['user', 'pinned' => false, 'createdAt'])

<div class="flex items-center gap-3 px-4 pt-4 pb-2">
    <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-sm font-semibold text-white"
    >
        {{ str($user->name)->substr(0, 2)->upper() }}
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</span>
            @if ($user->username)
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ '@' . $user->username }}</span>
            @endif
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $createdAt->diffForHumans(short: true) }}</span>
        </div>
    </div>

    @if ($pinned)
        <div class="flex items-center gap-1 text-xs text-amber-500">
            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
            <span>Fixado</span>
        </div>
    @endif
</div>
