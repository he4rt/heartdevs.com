<x-filament-panels::page>
    <div
        x-data="{
            count: @js($openOverlays),
            timer: null,
            init() {
                this.timer = setInterval(async () => (this.count = await this.$wire.countOpenOverlays()), 5000)
            },
            destroy() {
                clearInterval(this.timer)
            },
            get label() {
                if (this.count === null) return 'Tempo real fora do ar'
                if (this.count === 0) return 'Nenhuma overlay aberta'
                return this.count === 1 ? '1 overlay aberta agora' : `${this.count} overlays abertas agora`
            },
        }"
        class="-mt-2 flex flex-wrap items-center justify-end gap-x-2 gap-y-3 text-sm text-gray-600 dark:text-gray-300"
    >
        <span
            x-bind:class="count === null ? 'bg-danger-500' : count > 0 ? 'bg-success-500' : 'bg-gray-400'"
            @class([
                'size-2 rounded-full',
                'bg-danger-500' => $openOverlays === null,
                'bg-success-500' => $openOverlays > 0,
                'bg-gray-400' => $openOverlays === 0,
            ])
        ></span>
        <span x-text="label">
            @if ($openOverlays === null)
                Tempo real fora do ar
            @elseif ($openOverlays === 0)
                Nenhuma overlay aberta
            @else
                {{ $openOverlays === 1 ? '1 overlay aberta agora' : $openOverlays . ' overlays abertas agora' }}
            @endif
        </span>

        <x-filament-actions::group
            :actions="array_map(fn ($alertType) => ($this->testAlertAction)(['type' => $alertType->value]), $alertTypes)"
            :button="true"
            label="Testar alerta"
            icon="heroicon-m-bolt"
            color="gray"
            size="sm"
            dropdown-placement="bottom-end"
            class="ms-2"
        />
    </div>

    <x-filament::section heading="Como usar no OBS" icon="heroicon-o-information-circle" collapsible>
        <ol class="list-decimal space-y-1 ps-5 text-sm text-gray-600 dark:text-gray-300">
            <li>No OBS, adicione uma fonte do tipo <strong>Navegador</strong>.</li>
            <li>Cole o link da cena. As cenas usam 1920 × 1080; o Chat aceita qualquer tamanho.</li>
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
                    x-data="{
                        scale: 0,
                        background: 'dark',
                        init() {
                            const fit = () => (this.scale = this.$el.clientWidth / @js($item['previewWidth']))
                            fit()
                            new ResizeObserver(fit).observe(this.$el)
                        },
                    }"
                    x-bind:class="{
                        'bg-linear-to-br from-[#1a0b2e] via-[#241036] to-zinc-950': background === 'dark',
                        'bg-linear-to-br from-sky-100 via-white to-amber-50': background === 'light',
                        'bg-[image:repeating-conic-gradient(#d4d4d8_0_25%,#fafafa_0_50%)] bg-size-[24px_24px]': background === 'checker',
                    }"
                    class="relative aspect-video overflow-hidden bg-zinc-950"
                >
                    <iframe
                        src="{{ $item['previewUrl'] }}"
                        title="Prévia: {{ $item['scene']->getLabel() }}"
                        loading="lazy"
                        tabindex="-1"
                        aria-hidden="true"
                        class="pointer-events-none absolute top-0 left-0 origin-top-left border-0"
                        x-bind:style="{ transform: `scale(${scale})` }"
                        style="width: {{ $item['previewWidth'] }}px; height: {{ $item['previewWidth'] * 9 / 16 }}px; transform: scale(0)"
                    ></iframe>

                    @if ($item['hasTransparentBackground'])
                        <div
                            class="absolute top-2 left-2 flex gap-0.5 rounded-md bg-black/50 p-0.5 text-[11px] font-medium text-white/80"
                            role="group"
                            aria-label="Fundo da prévia"
                        >
                            @foreach (['dark' => 'Escuro', 'light' => 'Claro', 'checker' => 'Xadrez'] as $background => $label)
                                <button
                                    type="button"
                                    x-on:click="background = @js($background)"
                                    x-bind:class="background === @js($background) ? 'bg-white/25 text-white' : 'hover:bg-white/10'"
                                    class="rounded px-1.5 py-0.5"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <span
                        class="absolute right-3 bottom-3 rounded bg-black/40 px-1.5 py-0.5 font-mono text-[10px] text-white/70"
                    >
                        {{ $item['size'] }}
                    </span>
                </div>

                <div class="space-y-3 p-4">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $item['scene']->getLabel() }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item['scene']->getDescription() }}
                            </p>
                        </div>

                        @if ($item['settingsAction'])
                            {{ $this->{$item['settingsAction']} }}
                        @endif
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
