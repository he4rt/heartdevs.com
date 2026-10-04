<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
            <div class="shrink-0 sm:w-28">
                @if ($previous)
                    <x-filament::link
                        :href="\He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionPage::getUrl(['session' => $previous->id])"
                        icon="heroicon-m-chevron-left"
                        color="gray"
                        size="sm"
                    >
                        {{ $previous->started_at->format('d/m') }}
                    </x-filament::link>
                @endif
            </div>

            <div class="order-last w-full min-w-0 text-center sm:order-none sm:w-auto sm:flex-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if (filled($session->category))
                        {{ $session->category }} ·
                    @endif
                    {{ $session->started_at->format('d/m/Y H:i') }} →
                    {{ $session->ended_at?->format('H:i') ?? 'agora' }} · {{ $duration }}
                    @if ($session->isOpen())
                        <x-filament::badge color="danger" size="sm" class="ms-1 inline-flex">ao vivo</x-filament::badge>
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 justify-end sm:w-28">
                @if ($next)
                    <x-filament::link
                        :href="\He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionPage::getUrl(['session' => $next->id])"
                        icon="heroicon-m-chevron-right"
                        icon-position="after"
                        color="gray"
                        size="sm"
                    >
                        {{ $next->started_at->format('d/m') }}
                    </x-filament::link>
                @endif
            </div>
        </div>

        @if ($hasNoData)
            <p class="mt-4 rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                Nenhum evento e nenhuma mensagem chegaram nesta live. O mais provável é a Twitch não ter
                entregado os eventos. Confira a saúde da integração no Painel.
            </p>
        @else
            <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($comparison as $item)
                    <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">
                            <span aria-hidden="true">{{ $item['emoji'] }}</span>
                            {{ $item['label'] }}
                        </dt>
                        <dd class="flex items-baseline gap-2">
                            <span class="text-xl font-semibold text-gray-950 tabular-nums dark:text-white">
                                {{ number_format($item['value'], thousands_separator: '.') }}
                            </span>
                            @if ($item['delta'] !== null)
                                <span
                                    @class([
                                        'text-xs font-medium tabular-nums',
                                        'text-success-600 dark:text-success-400' => $item['delta'] > 0,
                                        'text-danger-600 dark:text-danger-400' => $item['delta'] < 0,
                                        'text-gray-400' => $item['delta'] === 0,
                                    ])
                                >
                                    @if ($item['delta'] > 0)
                                        ▲{{ number_format($item['delta'], thousands_separator: '.') }}
                                    @elseif ($item['delta'] < 0)
                                        ▼{{ number_format(abs($item['delta']), thousands_separator: '.') }}
                                    @else
                                        =
                                    @endif
                                </span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if ($baseline)
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Setas em relação à live de {{ $baseline->started_at->format('d/m') }}{{ $baseline->isNot($previous) ? ', a última com dados' : '' }}.
                </p>
            @endif
        @endif
    </x-filament::section>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-filament::section heading="Linha do tempo">
                @if ($timeline === [])
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhum follow, sub, bits ou raid nesta live.
                    </p>
                @else
                    <ol class="divide-y divide-gray-950/5 dark:divide-white/10">
                        @foreach ($timeline as $event)
                            <li wire:key="timeline-{{ $event['id'] }}" class="flex items-center gap-3 py-2">
                                <time
                                    datetime="{{ $event['at']->toIso8601String() }}"
                                    class="w-10 shrink-0 font-mono text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ $event['at']->format('H:i') }}
                                </time>
                                <span aria-hidden="true">{{ $event['type']->getEmoji() }}</span>
                                <p class="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-200">
                                    <span class="font-medium">{{ $event['type']->getLabel() }}</span>
                                    {{ filled($event['username']) ? '@' . $event['username'] : 'Anônimo' }}
                                    @if (filled($event['detail']))
                                        <span class="text-gray-500 dark:text-gray-400">· {{ $event['detail'] }}</span>
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-filament::section>
        </div>

        <x-filament::section
            heading="Quem mais falou"
            :description="$chatters === 1 ? '1 pessoa no chat' : $chatters . ' pessoas no chat'"
        >
            @if ($topChatters === [])
                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Ninguém falou no chat.</p>
            @else
                <ol class="space-y-2">
                    @foreach ($topChatters as $position => $chatter)
                        <li class="flex items-center gap-3 text-sm">
                            <span class="w-4 text-end text-xs text-gray-400 tabular-nums">{{ $position + 1 }}.</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-gray-950 dark:text-white">
                                {{ '@' . $chatter['username'] }}
                            </span>
                            <span class="text-xs text-gray-500 tabular-nums dark:text-gray-400">
                                {{ number_format($chatter['messages'], thousands_separator: '.') }}
                                {{ $chatter['messages'] === 1 ? 'msg' : 'msgs' }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
