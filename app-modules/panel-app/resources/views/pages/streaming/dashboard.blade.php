<x-filament-panels::page>
    @php
        $connection = $this->twitchConnection;
        $mainSource = $this->twitchSource;
        $missingScopes = $this->missingTwitchScopes;
    @endphp

    @if (! $connection)
        <x-filament::section>
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
        </x-filament::section>
    @else
        <div class="grid items-start gap-6 lg:grid-cols-3 lg:grid-rows-[auto_1fr]">
            <div class="min-w-0 lg:col-span-2">
                @if ($live)
                    <x-filament::section class="ring-2 ring-danger-500/40">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <x-filament::badge color="danger" icon="heroicon-m-signal">Ao vivo</x-filament::badge>
                            <span class="text-sm text-gray-500 dark:text-gray-400">há {{ $live['duration'] }}</span>
                            <x-filament::link
                                class="ms-auto"
                                :href="\He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionPage::getUrl(['session' => $live['session']->id])"
                                icon="heroicon-m-arrow-right"
                                icon-position="after"
                                size="sm"
                            >
                                Ver detalhe
                            </x-filament::link>
                        </div>

                        <p class="mt-3 truncate text-base font-semibold text-gray-950 dark:text-white">
                            {{ $live['session']->title ?? 'Sem título' }}
                        </p>
                        @if (filled($live['session']->category))
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $live['session']->category }}</p>
                        @endif

                        <x-panel-app::streaming.session-totals :rows="$live['rows']" class="mt-4" />

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
                @else
                    <x-filament::section>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            <x-filament::badge color="gray" icon="heroicon-m-signal-slash">Offline</x-filament::badge>
                            @if ($lastLive)
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    última live {{ $lastLive['session']->ended_at->locale('pt_BR')->diffForHumans() }}
                                </span>
                                <x-filament::link
                                    class="ms-auto"
                                    :href="\He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionPage::getUrl(['session' => $lastLive['session']->id])"
                                    icon="heroicon-m-arrow-right"
                                    icon-position="after"
                                    size="sm"
                                >
                                    Ver detalhe
                                </x-filament::link>
                            @endif
                        </div>

                        @if ($lastLive)
                            <p class="mt-3 truncate text-base font-semibold text-gray-950 dark:text-white">
                                {{ $lastLive['session']->title ?? 'Sem título' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if (filled($lastLive['session']->category))
                                    {{ $lastLive['session']->category }} ·
                                @endif
                                {{ $lastLive['session']->started_at->format('d/m H:i') }} →
                                {{ $lastLive['session']->ended_at->format('H:i') }} · {{ $lastLive['duration'] }}
                            </p>

                            @if ($lastLive['hasNoData'])
                                <p class="mt-4 rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                                    Nenhum evento e nenhuma mensagem chegaram nessa live. Confira a integração ao lado.
                                </p>
                            @else
                                <x-panel-app::streaming.session-totals :rows="$lastLive['rows']" class="mt-4" />

                                @if ($lastLive['baseline'])
                                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        Setas em relação à live de {{ $lastLive['baseline']->started_at->format('d/m') }}.
                                    </p>
                                @endif
                            @endif
                        @else
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                Nenhuma live ainda. Quando você entrar ao vivo, os números da live aparecem aqui.
                            </p>
                        @endif
                    </x-filament::section>
                @endif
            </div>

            <x-filament::section class="lg:col-start-3 lg:row-span-2 lg:row-start-1">
                <div class="flex items-start gap-3">
                    @if (filled($connection->metadata['avatar'] ?? null))
                        <img
                            src="{{ $connection->metadata['avatar'] }}"
                            alt=""
                            class="size-10 shrink-0 rounded-full ring-2 ring-purple-500/40"
                        />
                    @else
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-purple-500/10 text-purple-500">
                            <x-filament::icon
                                :icon="\He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Twitch->getIcon()"
                                class="size-5"
                            />
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                            {{ '@' . ($connection->metadata['username'] ?? $connection->external_account_id) }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            Twitch ·
                            {{ $mainSource?->chat_reader ? 'chat ' . \Illuminate\Support\Str::lcfirst($mainSource->chat_reader->getLabel()) : 'sem chat na overlay' }}
                        </p>
                    </div>

                    <x-filament-actions::group
                        :actions="array_filter([
                            $mainSource ? ($this->chatReaderAction)(['source' => $mainSource->id]) : null,
                            $mainSource ? ($this->toggleSourceAction)(['source' => $mainSource->id]) : null,
                            $this->disconnectTwitchAction,
                        ])"
                        icon="heroicon-m-ellipsis-vertical"
                        color="gray"
                        tooltip="Mais ações"
                        dropdown-placement="bottom-end"
                    />
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($missingScopes === [])
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">Pronto para alertas</x-filament::badge>
                    @else
                        <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle">Faltam permissões</x-filament::badge>
                        {{ $this->reauthorizeTwitchAction }}
                    @endif

                    @if ($mainSource && ! $mainSource->enabled)
                        <x-filament::badge color="gray" icon="heroicon-m-pause-circle">Fonte desligada</x-filament::badge>
                    @endif
                </div>

                @if ($mainSource)
                    @php
                        $healthChecks = $this->healthChecks;
                        $needingAttention = collect($healthChecks)->filter(fn ($check) => $check->status->needsAttention())->count();
                        $hasError = collect($healthChecks)->contains(fn ($check) => $check->status === \He4rt\Streaming\Health\HealthStatus::Error);
                    @endphp

                    <div wire:init="loadHealth" class="mt-5 border-t border-gray-950/5 pt-4 dark:border-white/10">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Integração</h3>
                            @if ($this->healthRequested)
                                @if ($needingAttention === 0)
                                    <x-filament::badge color="success" size="sm">Tudo certo</x-filament::badge>
                                @else
                                    <x-filament::badge :color="$hasError ? 'danger' : 'warning'" size="sm">
                                        {{ $needingAttention === 1 ? '1 item precisa de atenção' : $needingAttention . ' itens precisam de atenção' }}
                                    </x-filament::badge>
                                @endif
                            @endif
                        </div>

                        @if ($this->healthRequested)
                            <ul class="mt-2">
                                @foreach ($healthChecks as $check)
                                    @php($isOk = $check->status === \He4rt\Streaming\Health\HealthStatus::Ok)
                                    <li wire:key="health-{{ $check->key }}" class="flex items-start gap-2 py-1.5">
                                        <x-filament::icon
                                            :icon="$check->status->getIcon()"
                                            @class([
                                                'mt-0.5 size-4 shrink-0',
                                                'text-success-500' => $isOk,
                                                'text-info-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Waiting,
                                                'text-warning-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Warning,
                                                'text-danger-500' => $check->status === \He4rt\Streaming\Health\HealthStatus::Error,
                                            ])
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm text-gray-700 dark:text-gray-200"
                                                @if ($isOk) x-tooltip="{ content: @js($check->detail), theme: $store.theme }" @endif
                                            >
                                                {{ $check->title }}
                                            </p>
                                            @unless ($isOk)
                                                <p class="text-xs break-words text-gray-500 dark:text-gray-400">{{ $check->detail }}</p>
                                                @if ($check->fix === \He4rt\Streaming\Health\HealthFix::RepairSubscriptions)
                                                    <div class="mt-1.5">{{ $this->repairSubscriptionsAction }}</div>
                                                @elseif ($check->fix === \He4rt\Streaming\Health\HealthFix::Reconnect)
                                                    <div class="mt-1.5">{{ $this->reconnectTwitchAction }}</div>
                                                @endif
                                            @endunless
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-2 flex items-center justify-between gap-2 text-xs text-gray-400 dark:text-gray-500">
                                <span>Verificado às {{ now()->format('H:i:s') }}</span>
                                {{ $this->recheckHealthAction }}
                            </div>
                        @else
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::loading-indicator class="size-4" />
                                Verificando a integração…
                            </p>
                        @endif
                    </div>
                @endif

                @if ($otherSources->isNotEmpty())
                    <div class="mt-5 border-t border-gray-950/5 pt-4 dark:border-white/10">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Outras fontes</h3>
                        <ul class="mt-2">
                            @foreach ($otherSources as $source)
                                <li wire:key="source-{{ $source->id }}" class="flex items-center gap-2 py-1.5">
                                    <x-filament::icon :icon="$source->identity->provider->getIcon()" class="size-4 shrink-0 text-gray-400" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm text-gray-700 dark:text-gray-200">
                                            {{ '@' . ($source->identity->metadata['username'] ?? $source->identity->external_account_id) }}
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $source->enabled ? 'Ligada' : 'Desligada' }} ·
                                            {{ $source->chat_reader ? 'chat ' . \Illuminate\Support\Str::lcfirst($source->chat_reader->getLabel()) : 'sem chat na overlay' }}
                                        </p>
                                    </div>
                                    <x-filament-actions::group
                                        :actions="array_filter([
                                            $source->identity->provider === \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Twitch
                                                ? ($this->chatReaderAction)(['source' => $source->id])
                                                : null,
                                            ($this->toggleSourceAction)(['source' => $source->id]),
                                        ])"
                                        icon="heroicon-m-ellipsis-vertical"
                                        color="gray"
                                        tooltip="Mais ações"
                                        dropdown-placement="bottom-end"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-filament::section>

            <div class="min-w-0 lg:col-span-2">
                <x-filament::section heading="Atividade recente">
                    @if ($recentActivity === [])
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Nenhum evento ainda. Follows, subs, bits e raids do seu canal aparecem aqui.
                        </p>
                    @else
                        <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                            @foreach ($recentActivity as $event)
                                <li wire:key="activity-{{ $event['id'] }}" class="flex items-center gap-3 py-2">
                                    <span
                                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-base"
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
                                        {{ $event['at']->locale('pt_BR')->diffForHumans() }}
                                    </time>
                                    {{ ($this->replayAlertAction)(['event' => $event['id']]) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-filament::section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
