<x-filament-panels::page>
    @include('panel-app::pages.streaming.partials.preview-notice')

    @php
        $connection = $this->twitchConnection;
        $missingScopes = $this->missingTwitchScopes;
    @endphp

    <x-filament::section>
        @if ($connection)
            <div class="flex flex-wrap items-center gap-4">
                @if (filled($connection->metadata['avatar'] ?? null))
                    <img
                        src="{{ $connection->metadata['avatar'] }}"
                        alt=""
                        class="size-12 rounded-full ring-2 ring-purple-500/40"
                    />
                @else
                    <div class="flex size-12 items-center justify-center rounded-full bg-purple-500/10 text-purple-500">
                        <x-filament::icon
                            :icon="\He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Twitch->getIcon()"
                            class="size-6"
                        />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-semibold text-gray-950 dark:text-white">
                        {{ '@' . ($connection->metadata['username'] ?? $connection->external_account_id) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Canal da Twitch conectado à He4rt</p>
                </div>

                @if ($missingScopes === [])
                    <x-filament::badge color="success" icon="heroicon-m-check-circle">Pronto para alertas</x-filament::badge>
                @else
                    <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">
                        Faltam permissões
                    </x-filament::badge>
                    <x-filament::button tag="a" :href="$profileUrl" size="sm" color="warning">
                        Reautorizar no perfil
                    </x-filament::button>
                @endif
            </div>
        @else
            <div class="flex flex-col items-center gap-3 py-6 text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-purple-500/10 text-purple-500">
                    <x-filament::icon
                        :icon="\He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Twitch->getIcon()"
                        class="size-6"
                    />
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-950 dark:text-white">Conecte sua Twitch</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Os alertas e as overlays usam o seu canal. Conecte a Twitch na área de conexões do perfil.
                    </p>
                </div>
                <x-filament::button tag="a" :href="$profileUrl" size="sm">Conectar no perfil</x-filament::button>
            </div>
        @endif
    </x-filament::section>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div
                wire:key="stat-{{ $stat['type']->value }}"
                class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <p class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span aria-hidden="true">{{ $stat['type']->getEmoji() }}</span>
                    {{ $stat['type']->getLabel() }}
                </p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $stat['value'] }}
                </p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">últimos 30 dias</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section
            heading="Testar alertas"
            description="Dispare um alerta de exemplo para conferir a overlay."
        >
            <div class="grid grid-cols-2 gap-2">
                @foreach ($alertTypes as $alertType)
                    <x-filament::button
                        wire:key="test-alert-{{ $alertType->value }}"
                        wire:click="sendTestAlert('{{ $alertType->value }}')"
                        color="gray"
                    >
                        <span aria-hidden="true">{{ $alertType->getEmoji() }}</span>
                        {{ $alertType->getLabel() }}
                    </x-filament::button>
                @endforeach
            </div>
        </x-filament::section>

        <div class="lg:col-span-2">
            <x-filament::section heading="Atividade recente">
                <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                    @foreach ($recentActivity as $event)
                        <li wire:key="activity-{{ $loop->index }}" class="flex items-center gap-3 py-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg text-lg"
                                style="box-shadow: inset 0 0 0 2px {{ $event['type']->getAccent() }}"
                                aria-hidden="true"
                            >
                                {{ $event['type']->getEmoji() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                    {{ '@' . $event['username'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event['type']->getLabel() }}@if (filled($event['detail'])) · {{ $event['detail'] }}@endif
                                </p>
                            </div>
                            <time
                                datetime="{{ $event['at']->toIso8601String() }}"
                                class="shrink-0 text-xs text-gray-400 dark:text-gray-500"
                            >
                                {{ $event['at']->diffForHumans() }}
                            </time>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
