@props (['user' => null, 'pinned' => false, 'createdAt'])

@php($displayName = $user?->name ?? 'Usuário removido')

<div class="flex items-center gap-3 px-3 pt-3 pb-2 sm:px-4 sm:pt-4">
    <div
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-500 text-xs font-semibold text-white sm:h-10 sm:w-10 sm:text-sm"
    >
        {{ str($displayName)->substr(0, 2)->upper() }}
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $displayName }}</span>
            @if ($user?->username)
                <span
                    class="hidden text-sm text-gray-500 sm:inline dark:text-gray-400"
                    >{{ '@' . $user->username }}</span
                >
            @endif
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $createdAt->diffForHumans(short: true) }}</span>
        </div>
    </div>

    @if ($pinned)
        <div class="flex shrink-0 items-center gap-1 text-xs text-amber-500">
            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">Fixado</span>
        </div>
    @endif
</div>
