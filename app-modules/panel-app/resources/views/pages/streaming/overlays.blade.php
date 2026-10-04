<x-filament-panels::page>
    @include('panel-app::pages.streaming.partials.preview-notice')

    <x-filament::section heading="Como usar no OBS" icon="heroicon-o-information-circle" collapsible>
        <ol class="list-decimal space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-300">
            <li>No OBS, adicione uma fonte do tipo <strong>Navegador</strong>.</li>
            <li>Cole o link da cena e defina largura 1920 e altura 1080.</li>
            <li>Não compartilhe os links: quem tiver um deles vê a sua overlay.</li>
        </ol>
    </x-filament::section>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($scenes as $item)
            <div
                wire:key="scene-{{ $item['scene']->value }}-{{ $overlayToken }}"
                class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div
                    class="relative flex aspect-video items-center justify-center bg-linear-to-br from-[#1a0b2e] via-[#241036] to-zinc-950"
                >
                    <span class="text-xs font-semibold tracking-[0.2em] text-purple-200/70 uppercase">
                        {{ $item['scene']->getLabel() }}
                    </span>
                    <span
                        class="absolute right-3 bottom-3 rounded bg-black/40 px-1.5 py-0.5 font-mono text-[10px] text-white/70"
                    >
                        1920 × 1080
                    </span>
                </div>

                <div class="space-y-3 p-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $item['scene']->getLabel() }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $item['scene']->getDescription() }}
                        </p>
                    </div>

                    <div
                        x-data="{ revealed: false, copied: false }"
                        class="flex items-center gap-1 rounded-lg bg-gray-50 p-1 ps-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
                    >
                        <code
                            class="min-w-0 flex-1 truncate font-mono text-xs text-gray-600 dark:text-gray-300"
                            x-text="revealed ? @js($item['url']) : @js($item['maskedUrl'])"
                        >
                            {{ $item['maskedUrl'] }}
                        </code>
                        <span x-show="copied" x-cloak class="text-xs text-success-600 dark:text-success-400">Copiado</span>
                        <x-filament::icon-button
                            icon="heroicon-m-eye"
                            label="Mostrar link"
                            color="gray"
                            size="sm"
                            x-on:click="revealed = ! revealed"
                        />
                        <x-filament::icon-button
                            icon="heroicon-m-clipboard-document"
                            label="Copiar link"
                            color="gray"
                            size="sm"
                            x-on:click="window.navigator.clipboard.writeText({{ Js::from($item['url']) }}); copied = true; setTimeout(() => copied = false, 1500)"
                        />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
