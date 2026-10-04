<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="description">O que você fizer aqui vale só para a overlay. Na Twitch nada muda.</x-slot>

        @if (! $hasChatSource)
            <div class="flex flex-col items-center gap-2 py-6 text-center">
                <p class="text-base font-semibold text-gray-950 dark:text-white">Nenhuma fonte mostra chat na overlay</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Em Minha Live › Painel, escolha quem lê o chat na lista de fontes.
                </p>
                <x-filament::link
                    :href="\He4rt\PanelApp\Clusters\Streaming\Pages\StreamDashboardPage::getUrl()"
                    icon="heroicon-m-arrow-right"
                    icon-position="after"
                >
                    Ir para o Painel
                </x-filament::link>
            </div>
        @else
            <div
                x-data="{
                    paused: false,
                    timer: null,
                    init() {
                        this.timer = setInterval(() => {
                            if (! this.paused) this.$wire.$refresh()
                        }, 3000)
                    },
                    destroy() {
                        clearInterval(this.timer)
                    },
                }"
            >
                <div class="mb-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="size-2 rounded-full bg-success-500" x-show="! paused"></span>
                    <span x-show="! paused">Ao vivo · atualiza a cada 3 s</span>
                    <span x-show="paused" x-cloak class="font-medium text-warning-600 dark:text-warning-400">
                        Pausado enquanto o mouse está na lista
                    </span>
                </div>

                @if ($lines === [])
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma mensagem ainda. O chat das fontes ligadas aparece aqui.
                    </p>
                @else
                    <ul
                        x-on:mouseenter="paused = true"
                        x-on:mouseleave="paused = false"
                        class="flex max-h-[60vh] flex-col-reverse divide-y divide-y-reverse divide-gray-950/5 overflow-y-auto dark:divide-white/10"
                    >
                        @php($dividerShown = false)
                        @foreach ($lines as $line)
                            @if ($line['isBeforeClear'] && ! $dividerShown)
                                @php($dividerShown = true)
                                <li class="py-2 text-center text-xs text-gray-400 dark:text-gray-500">
                                    Chat da overlay limpo às {{ $clearedAt->format('H:i') }}
                                </li>
                            @endif

                            <li
                                wire:key="chat-line-{{ $line['id'] }}"
                                @class([
                                    'flex items-start gap-3 py-2',
                                    'opacity-50' => $line['isBeforeClear'] || $line['isDeleted'],
                                ])
                            >
                                <time
                                    datetime="{{ $line['at']?->toIso8601String() }}"
                                    class="w-10 shrink-0 pt-0.5 font-mono text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ $line['at']?->format('H:i') }}
                                </time>

                                <div class="min-w-0 flex-1 text-sm">
                                    @foreach ($line['badges'] as $badge)
                                        <img src="{{ $badge }}" alt="" class="me-0.5 inline-block size-4 align-[-0.15em]" />
                                    @endforeach
                                    <span
                                        class="font-semibold"
                                        @if (filled($line['color'])) style="color: {{ $line['color'] }}" @endif
                                    >
                                        {{ $line['username'] }}
                                    </span>
                                    <span @class(['text-gray-700 dark:text-gray-200', 'line-through' => $line['isDeleted']])>
                                        {{ $line['text'] }}
                                    </span>

                                    @if ($line['isDeleted'])
                                        <x-filament::badge color="danger" size="sm" class="ms-1 inline-flex">
                                            apagada pela moderação
                                        </x-filament::badge>
                                    @elseif ($line['isHidden'])
                                        <x-filament::badge color="gray" size="sm" class="ms-1 inline-flex">
                                            oculta na overlay
                                        </x-filament::badge>
                                    @endif

                                    @if ($line['isMuted'])
                                        <x-filament::badge color="warning" size="sm" class="ms-1 inline-flex">
                                            silenciado na overlay
                                        </x-filament::badge>
                                    @endif
                                </div>

                                @unless ($line['isDeleted'])
                                    <div class="flex shrink-0 items-center gap-1">
                                        @unless ($line['isHidden'] || $line['isBeforeClear'])
                                            {{ ($this->hideMessageAction)(['message' => $line['id']]) }}
                                        @endunless

                                        @unless ($line['isMuted'])
                                            {{ ($this->muteChatterAction)(['message' => $line['id']]) }}
                                        @endunless
                                    </div>
                                @endunless
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </x-filament::section>

    @if ($mutedChatters !== [])
        <x-filament::section
            heading="Silenciados na overlay"
            description="As mensagens deles continuam na Twitch e aqui, mas não aparecem na overlay."
        >
            <ul class="divide-y divide-gray-950/5 dark:divide-white/10">
                @foreach ($mutedChatters as $chatter)
                    <li
                        wire:key="muted-{{ $chatter->platform->value }}-{{ $chatter->chatterId }}"
                        class="flex items-center gap-3 py-2"
                    >
                        <p class="min-w-0 flex-1 truncate text-sm font-medium text-gray-950 dark:text-white">
                            {{ '@' . $chatter->displayName }}
                        </p>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            desde {{ $chatter->mutedAt->timezone(config('app.timezone'))->format('d/m H:i') }}
                        </span>
                        {{ ($this->unmuteChatterAction)(['platform' => $chatter->platform->value, 'chatter' => $chatter->chatterId]) }}
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
