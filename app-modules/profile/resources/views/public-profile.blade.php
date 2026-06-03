<x-portal::layouts.app title="{{ $profile->nickname ?? $user->name }} — He4rt Devs">
    <div class="min-h-screen bg-gray-50 dark:bg-[#0d1117]">
        <div class="mx-auto max-w-5xl px-4 pt-8 pb-6">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:gap-6">
                <div class="shrink-0">
                    <div
                        class="flex size-24 items-center justify-center rounded-full bg-purple-600 text-3xl font-bold text-white ring-4 ring-gray-50 sm:size-28 dark:ring-[#0d1117]"
                    >
                        {{ strtoupper(substr($user->name, 0, 1)) }}{{
                            strtoupper(
                                substr(explode(' ', $user->name)[1] ?? '', 0, 1),
                            )
                        }}
                    </div>
                </div>

                <div class="flex-1 text-center sm:text-left">
                    @if ($profile->available_for_proposals)
                        <h1 class="text-text-high text-2xl font-bold">{{ $profile->nickname ?? $user->name }}</h1>
                        <p class="text-text-medium mt-1 text-sm">
                            @if ($profile->headline)
                                {{ $profile->headline }}
                            @elseif ($profile->seniority_level)
                                {{
                                    __(
                                        'profile::enums.seniority_level.' . $profile->seniority_level->value,
                                    )
                                }}
                                @if ($profile->years_experience) ·{{ $profile->years_experience }}anos de experiência @endif
                            @endif
                        </p>
                        <span
                            class="mt-2 -ml-1 inline-flex items-center gap-1.5 rounded-full border border-green-500/30 bg-green-500/10 px-3 py-1 text-xs font-medium text-green-600 dark:text-green-400"
                        >
                            Disponível para propostas
                            <x-filament::icon icon="heroicon-s-check-circle" class="size-3.5" />
                        </span>
                    @else
                        <h1 class="text-text-high mt-8 text-2xl font-bold">{{ $profile->nickname ?? $user->name }}</h1>
                        <p class="text-text-medium mt-2 text-sm">
                            @if ($profile->headline)
                                {{ $profile->headline }}
                            @elseif ($profile->seniority_level)
                                {{
                                    __(
                                        'profile::enums.seniority_level.' . $profile->seniority_level->value,
                                    )
                                }}
                                @if ($profile->years_experience) ·{{ $profile->years_experience }}anos de experiência @endif
                            @endif
                        </p>
                    @endif

                    <div class="mt-3 flex items-center justify-center gap-2 sm:justify-start">
                        @if ($profile->social_links)
                            @foreach (['github', 'linkedin'] as $platform)
                                @if ($url = $profile->social_links[$platform] ?? null)
                                    <a
                                        href="{{ $url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-text-medium hover:text-text-high flex size-8 items-center justify-center rounded-full bg-gray-100 transition-colors dark:bg-white/5"
                                    >
                                        @switch ($platform)
                                            @case ('github')
                                                <x-filament::icon icon="fab-github" class="size-4" />
                                                @break
                                            @case ('linkedin')
                                                <x-filament::icon icon="fab-linkedin" class="size-4" />
                                                @break
                                        @endswitch
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-4 pb-16">
            <div class="flex flex-col gap-6 lg:grid lg:grid-cols-3">
                <div class="flex flex-col gap-6 lg:col-span-2">
                    @if ($profile->about || $profile->seniority_level || $profile->years_experience)
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-[#161b22]"
                        >
                            <h2 class="text-text-high mb-3 text-base font-semibold">Sobre</h2>
                            @if ($profile->about)
                                <p class="text-text-medium text-sm leading-relaxed">{{ $profile->about }}</p>
                            @endif
                        </div>
                    @endif

                    <div class="rounded-xl border border-purple-500/20 bg-white p-5 lg:hidden dark:bg-[#161b22]">
                        <h2 class="text-text-high mb-4 flex items-center gap-2 text-sm font-semibold">
                            Resumo para Recrutadores
                            <x-filament::icon icon="heroicon-s-document-text" class="size-4 text-purple-400" />
                        </h2>
                        <div class="grid grid-cols-2 gap-4">
                            @if ($profile->seniority_level)
                                <div>
                                    <p class="text-text-medium text-xs">Senioridade</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{
                                        __(
                                            'profile::enums.seniority_level.' . $profile->seniority_level->value,
                                        )
                                    }}</p>
                                </div>
                            @endif
                            @if ($profile->years_experience)
                                <div>
                                    <p class="text-text-medium text-xs">Experiência</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{ $profile->years_experience }} anos</p>
                                </div>
                            @endif
                            @if ($user->address)
                                <div>
                                    <p class="text-text-medium text-xs">Localização</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{ $user->address->city }}{{
                                        $user->address->state
                                            ? ', ' . $user->address->state
                                            : ''
                                    }}</p>
                                </div>
                            @endif
                            @if ($profile->start_availability)
                                <div>
                                    <p class="text-text-medium text-xs">Início</p>
                                    <p class="mt-0.5 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 px-2 py-0.5 text-sm font-semibold text-white">
                                        {{
                                            __(
                                                'profile::enums.start_availability.' . $profile->start_availability->value,
                                            )
                                        }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($user->character)
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-5 lg:hidden dark:border-white/5 dark:bg-[#161b22]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-600/20"
                                >
                                    <x-filament::icon
                                        icon="heroicon-s-star"
                                        class="size-5 text-purple-500 dark:text-purple-400"
                                    />
                                </div>
                                <div>
                                    <p class="text-text-medium text-xs">Nível da Comunidade</p>
                                    <p class="text-text-high text-lg font-bold">{{ $user->character->level }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/5">
                                    <div
                                        class="h-full rounded-full bg-purple-500"
                                        style="width: {{ $user->character->percentage_experience }}%"
                                    ></div>
                                </div>
                                <p class="text-text-medium mt-1 text-xs">{{ number_format($user->character->experience) }} XP · {{ number_format($user->character->experience_progress) }} para o próximo nível</p>
                            </div>
                        </div>
                    @endif

                    @if ($user->character && $user->character->badges->isNotEmpty())
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-[#161b22]"
                        >
                            <h2 class="text-text-high mb-3 text-base font-semibold">Badges He4rt</h2>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($user->character->badges as $badge)
                                    <div
                                        class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/5 dark:bg-[#1c2128]"
                                    >
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-600/20"
                                        >
                                            @if ($badge->getFirstMediaUrl('badge'))
                                                <img
                                                    src="{{ $badge->getFirstMediaUrl('badge') }}"
                                                    alt="{{ $badge->name }}"
                                                    class="size-5 object-contain"
                                                />
                                            @else
                                                <x-filament::icon
                                                    icon="heroicon-s-star"
                                                    class="size-5 text-purple-500 dark:text-purple-400"
                                                />
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-text-high text-sm font-medium">{{ $badge->name }}</h4>
                                            @if ($badge->description)
                                                <p class="text-text-medium mt-0.5 line-clamp-2 text-xs">{{ $badge->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($connectedAccounts->isNotEmpty())
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-[#161b22]"
                        >
                            <h2 class="text-text-high mb-3 text-base font-semibold">Contas Conectadas</h2>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($connectedAccounts as $account)
                                    <a
                                        href="{{ $account['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-white/5 dark:bg-[#1c2128] dark:hover:bg-[#21262d]"
                                    >
                                        <div class="text-text-medium flex size-8 shrink-0 items-center justify-center">
                                            @switch ($account['provider'])
                                                @case ('github')
                                                    <x-filament::icon icon="fab-github" class="size-5" />
                                                    @break
                                                @case ('discord')
                                                    <x-filament::icon icon="fab-discord" class="size-5" />
                                                    @break
                                                @case ('linkedin')
                                                    <x-filament::icon icon="fab-linkedin" class="size-5" />
                                                    @break
                                                @case ('twitter')
                                                    <x-filament::icon icon="fab-x-twitter" class="size-5" />
                                                    @break
                                                @case ('devto')
                                                    <x-filament::icon icon="fab-dev" class="size-5" />
                                                    @break
                                                @case ('youtube')
                                                    <x-filament::icon icon="fab-youtube" class="size-5" />
                                                    @break
                                                @case ('instagram')
                                                    <x-filament::icon icon="fab-instagram" class="size-5" />
                                                    @break
                                                @default
                                                    <x-filament::icon icon="heroicon-o-link" class="size-5" />
                                            @endswitch
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-text-high text-sm font-medium">{{ ucfirst($account['label']) }}</p>
                                            <p class="text-text-medium truncate text-xs">{{ parse_url($account['url'], PHP_URL_HOST) }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="hidden flex-col gap-6 lg:flex">
                    <div class="rounded-xl border border-purple-500/20 bg-white p-5 dark:bg-[#161b22]">
                        <h2 class="text-text-high mb-4 flex items-center gap-2 text-sm font-semibold">
                            Resumo para Recrutadores
                            <x-filament::icon icon="heroicon-s-document-text" class="size-4 text-purple-400" />
                        </h2>
                        <div class="grid grid-cols-2 gap-4">
                            @if ($profile->seniority_level)
                                <div>
                                    <p class="text-text-medium text-xs">Senioridade</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{
                                        __(
                                            'profile::enums.seniority_level.' . $profile->seniority_level->value,
                                        )
                                    }}</p>
                                </div>
                            @endif
                            @if ($profile->years_experience)
                                <div>
                                    <p class="text-text-medium text-xs">Experiência</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{ $profile->years_experience }} anos</p>
                                </div>
                            @endif
                            @if ($user->address)
                                <div>
                                    <p class="text-text-medium text-xs">Localização</p>
                                    <p class="text-text-high mt-0.5 text-sm font-medium">{{ $user->address->city }}{{
                                        $user->address->state
                                            ? ', ' . $user->address->state
                                            : ''
                                    }}</p>
                                </div>
                            @endif
                            @if ($profile->start_availability)
                                <div>
                                    <p class="text-text-medium text-xs">Início</p>
                                    <p class="mt-0.5 rounded-md bg-gradient-to-r from-purple-600 to-indigo-600 px-2 py-0.5 text-sm font-semibold text-white">
                                        {{
                                            __(
                                                'profile::enums.start_availability.' . $profile->start_availability->value,
                                            )
                                        }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Level --}}
                    @if ($user->character)
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-[#161b22]"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-600/20"
                                >
                                    <x-filament::icon
                                        icon="heroicon-s-star"
                                        class="size-5 text-purple-500 dark:text-purple-400"
                                    />
                                </div>
                                <div>
                                    <p class="text-text-medium text-xs">Nível da Comunidade</p>
                                    <p class="text-text-high text-lg font-bold">{{ $user->character->level }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/5">
                                    <div
                                        class="h-full rounded-full bg-purple-500"
                                        style="width: {{ $user->character->percentage_experience }}%"
                                    ></div>
                                </div>
                                <p class="text-text-medium mt-1 text-xs">{{ number_format($user->character->experience) }} XP · {{ number_format($user->character->experience_progress) }} para o próximo nível</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-portal::layouts.app>
