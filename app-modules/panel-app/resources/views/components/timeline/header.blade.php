@props (['user' => null, 'pinned' => false, 'createdAt'])

@php
    $displayName = $user?->name ?? 'Usuário removido';
@endphp

<div class="flex items-center gap-3 px-3 pt-3 pb-2 sm:px-4 sm:pt-4">
    <x-panel-app::profile-link :user="$user" class="shrink-0">
        <x-panel-app::user-avatar :user="$user" size="lg" />
    </x-panel-app::profile-link>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <x-panel-app::profile-link
                :user="$user"
                class="group flex min-w-0 items-center gap-x-2"
            >
                <span
                    class="truncate text-sm font-semibold text-gray-900 group-hover:underline dark:text-white"
                    >{{ $displayName }}</span
                >
                @if ($user?->username)
                    <span
                        class="hidden text-sm text-gray-500 sm:inline dark:text-gray-400"
                        >{{ '@' . $user->username }}</span
                    >
                @endif
            </x-panel-app::profile-link>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $createdAt->diffForHumans(short: true) }}</span>
        </div>
    </div>

    @if ($pinned && config('he4rt.features.timeline_pin'))
        <div class="flex shrink-0 items-center gap-1 text-xs text-amber-500">
            <x-heroicon-s-map-pin class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">Fixado</span>
        </div>
    @endif
</div>
