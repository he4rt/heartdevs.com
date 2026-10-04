<x-filament-panels::page>
    @php
        $connection = $this->twitchConnection;
        $missingScopes = $this->missingTwitchScopes;
    @endphp

    @if ($live)
        <x-filament::section class="ring-2 ring-danger-500/40">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <x-filament::badge color="danger" icon="heroicon-m-signal">Ao vivo</x-filament::badge>
                <span class="text-sm text-gray-500 dark:text-gray-400">há {{ $live['duration'] }}</span>
                <p class="min-w-0 flex-1 truncate text-end text-sm text-gray-700 dark:text-gray-200">
                    <span class="font-medium">{{ $live['session']->title ?? 'Sem título' }}</span>
                    @if (filled($live['session']->category))
                        <span class="text-gray-500 dark:text-gray-400">· {{ $live['session']->category }}</span>
                    @endif
                </p>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($live['totals'] as $total)
                    <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">
                            <span aria-hidden="true">{{ $total['emoji'] }}</span>
                            {{ $total['label'] }}
                        </dt>
                        <dd class="text-xl font-semibold text-gray-950 tabular-nums dark:text-white">
                            {{ number_format($total['value'], thousands_separator: '.') }}
                        </dd>
                    </div>
                @endforeach
            </dl>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ str_replace('.', ',', (string) $live['messagesPerMinute']) }} msgs/min nos últimos 5 min ·
                {{ $live['chatters'] }} {{ $live['chatters'] === 1 ? 'chatter' : 'chatters' }} ·
                @if ($live['openOverlays'] === null)
                    tempo real fora do ar
                @elseif ($live['openOverlays'] === 0)
                    nenhuma overlay aberta
                @else
                    {{ $live['openOverlays'] === 1 ? '1 overlay aberta' : $live['openOverlays'] . ' overlays abertas' }}
                @endif
            </p>
        </x-filament::section>
    @endif

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

                <div class="flex flex-wrap items-center gap-2">
                    @if ($missingScopes === [])
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">
                            Pronto para alertas
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">
                            Faltam permissões
                        </x-filament::badge>
                        {{ $this->reauthorizeTwitchAction }}
                    @endif

                    {{ $this->disconnectTwitchAction }}
                </div>
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
                        Os alertas e as overlays usam o seu canal. Conecte a Twitch para começar.
                    </p>
                </div>
                {{ $this->connectTwitchAction }}
            </div>
        @endif
    </x-filament::section>

    @if ($connection && $this->twitchSource)
        @php
            $healthChecks = $this->healthChecks;
            $needingAttention = collect($healthChecks)->filter(fn ($check) => $check->status->needsAttention())->count();
            $hasError = collect($healthChecks)->contains(fn ($check) => $check->status === \He4rt\Streaming\Health\HealthStatus::Error);
        @endphp

        <x-filament::section heading="Saúde da integração" wire:init="loadHealth">
            @if ($this->healthRequested)
                <x-slot name="afterHeader">
                    @if ($needingAttention === 0)
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">Tudo certo</x-filament::badge>
                    @else
                        <x-filament::badge :color="$hasError ? 'danger' : 'warning'" icon="heroicon-m-exclamation-triangle">
                            {{ $needingAttention === 1 ? '1 item precisa de atenção' : $needingAttention . ' itens precisam de atenção' }}
                        </x-filament::badge>
                    @endif
                </x-slot>

                <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                    @foreach ($healthChecks as $check)
                        <li wire:key="health-{{ $check->key }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5">
                            <x-filament::icon
                                :icon="$check->status->getIcon()"
                                @class([
                                    'size-5 shrink-0',
                                    'text-success-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Ok,
                                    'text-info-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Waiting,
                                    'text-warning-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Warning,
                                    'text-danger-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Error,
                                ])
                            />
                            <p class="w-40 shrink-0 text-sm font-medium text-gray-950 dark:text-white">{{ $check->title }}</p>
                            <p class="min-w-0 flex-1 text-sm break-words text-gray-500 dark:text-gray-400">{{ $check->detail }}</p>

                            @if ($check->fix === \He4rt\Streaming\Health\HealthFix::RepairSubscriptions)
                                {{ $this->repairSubscriptionsAction }}
                            @elseif ($check->fix === \He4rt\Streaming\Health\HealthFix::Reconnect)
                                {{ $this->reconnectTwitchAction }}
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 flex items-center justify-end gap-3 text-xs text-gray-400 dark:text-gray-500">
                    <span>Verificado às {{ now()->format('H:i:s') }}</span>
                    {{ $this->recheckHealthAction }}
                </div>
            @else
                <p class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::loading-indicator class="size-4" />
                    Verificando a integração…
                </p>
            @endif
        </x-filament::section>
    @endif

    @if ($this->sources->isNotEmpty())
        <x-filament::section heading="Fontes" description="Canais que mandam alertas e chat para as suas overlays.">
            <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                @foreach ($this->sources as $source)
                    <li wire:key="source-{{ $source->id }}" class="flex flex-wrap items-center gap-3 py-3">
                        <x-filament::icon :icon="$source->identity->provider->getIcon()" class="size-5 shrink-0 text-gray-400" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                {{ '@' . ($source->identity->metadata['username'] ?? $source->identity->external_account_id) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $source->chat_reader?->getLabel() ?? 'Sem chat na overlay' }}
                            </p>
                        </div>
                        <x-filament::badge :color="$source->enabled ? 'success' : 'gray'">
                            {{ $source->enabled ? 'Ligada' : 'Desligada' }}
                        </x-filament::badge>
                        @if ($source->identity->provider === \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Twitch)
                            {{ ($this->chatReaderAction)(['source' => $source->id]) }}
                        @endif

                        {{ ($this->toggleSourceAction)(['source' => $source->id]) }}
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

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
                    <div wire:key="test-alert-{{ $alertType->value }}">
                        {{ ($this->testAlertAction)(['type' => $alertType->value]) }}
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="lg:col-span-2">
            <x-filament::section heading="Atividade recente">
                @if ($recentActivity === [])
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhum evento ainda. Follows, subs, bits e raids do seu canal aparecem aqui.
                    </p>
                @endif

                <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                    @foreach ($recentActivity as $event)
                        <li wire:key="activity-{{ $event['id'] }}" class="flex items-center gap-3 py-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg text-lg"
                                style="box-shadow: inset 0 0 0 2px {{ $event['type']->getAccent() }}"
                                aria-hidden="true"
                            >
                                {{ $event['type']->getEmoji() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                    {{ filled($event['username']) ? '@' . $event['username'] : 'Anônimo' }}
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
                            {{ ($this->replayAlertAction)(['event' => $event['id']]) }}
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
