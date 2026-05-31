@assets
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <style>
        .showcase-canvas-bg {
            background-image:
                linear-gradient(45deg, #222 25%, transparent 25%), linear-gradient(-45deg, #222 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #222 75%), linear-gradient(-45deg, transparent 75%, #222 75%);
            background-size: 20px 20px;
            background-position:
                0 0,
                0 10px,
                10px -10px,
                -10px 0;
            background-color: #1a1a1a;
        }

        .showcase-user-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
        }

        .showcase-user-card img {
            border-radius: 50%;
            object-fit: cover;
            background: #2a2a4a;
            flex-shrink: 0;
        }

        .showcase-ctrl label {
            font-size: 12px;
            white-space: nowrap;
            min-width: 100px;
        }

        .showcase-ctrl input[type='range'] {
            flex: 1;
            accent-color: #7c3aed;
            height: 4px;
        }

        .showcase-ctrl .val {
            font-size: 11px;
            min-width: 36px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
    </style>
@endassets

<x-filament-panels::page>
    <div
        x-data="{
        width: 900,
        columns: 8,
        gap: 16,
        padding: 32,
        borderRadius: 16,
        avatarSize: 56,
        avatarBorder: 0,
        avatarBorderColor: '#7c3aed',
        fontName: 12,
        fontUser: 10,
        fontMsgs: 10,
        fontTitle: 24,
        showTitle: true,
        showSubtitle: true,
        showName: true,
        showUsername: true,
        showMessages: false,
        showBackground: true,
        opacity: 85,
        bgColor: '#000000',
        textColor: '#ffffff',
        exportScale: 2,
        showcaseTitle: 'Reunião {{ now()->format("d/m") }}',
        exporting: false,

        bgRgba() {
            const r = parseInt(this.bgColor.slice(1,3), 16);
            const g = parseInt(this.bgColor.slice(3,5), 16);
            const b = parseInt(this.bgColor.slice(5,7), 16);
            return this.showBackground
                ? `rgba(${r},${g},${b},${this.opacity/100})`
                : 'transparent';
        },

        applyPreset(name) {
            const presets = {
                story:  { width: 540, columns: 5, gap: 12, avatarSize: 48, padding: 24, fontName: 10, fontTitle: 20 },
                post:   { width: 800, columns: 7, gap: 16, avatarSize: 56, padding: 32, fontName: 11, fontTitle: 22 },
                wide:   { width: 1280, columns: 12, gap: 16, avatarSize: 56, padding: 32, fontName: 12, fontTitle: 24 },
                banner: { width: 1920, columns: 16, gap: 12, avatarSize: 48, padding: 24, fontName: 10, fontTitle: 28 },
            };
            Object.assign(this, presets[name] || {});
        },

        async exportPNG() {
            this.exporting = true;
            try {
                const el = document.getElementById('showcase');
                const canvas = await html2canvas(el, {
                    scale: this.exportScale,
                    useCORS: true,
                    allowTaint: true,
                    logging: false,
                });
                const link = document.createElement('a');
                link.download = 'meeting-showcase.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } finally {
                this.exporting = false;
            }
        }
    }"
    >
        {{-- Filters --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
            <flux:input wire:model="channelId" label="Channel ID" placeholder="Ex: 123456789012345678" />
            <flux:input type="datetime-local" wire:model="startDate" label="Data Início" />
            <flux:input type="datetime-local" wire:model="endDate" label="Data Fim" />
            <flux:button wire:click="loadParticipants" variant="primary" class="h-10">
                <span wire:loading.remove wire:target="loadParticipants">Carregar Participantes</span>
                <span wire:loading wire:target="loadParticipants">Carregando...</span>
            </flux:button>
        </div>

        @if ($loaded)
            <div class="flex flex-col gap-6 lg:flex-row">
                {{-- Showcase Preview --}}
                <div class="showcase-canvas-bg flex-1 overflow-auto rounded-xl">
                    <div class="flex min-h-[500px] items-center justify-center p-10">
                        <div
                            id="showcase"
                            :style="{
                                width: width + 'px',
                                padding: padding + 'px',
                                borderRadius: borderRadius + 'px',
                                background: bgRgba(),
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                gap: '24px',
                            }"
                        >
                            {{-- Title --}}
                            <div
                                x-show="showTitle"
                                x-text="showcaseTitle"
                                x-on:input="showcaseTitle = $event.target.textContent"
                                contenteditable="true"
                                spellcheck="false"
                                :style="{
                                    fontSize: fontTitle + 'px',
                                    fontWeight: '700',
                                    color: textColor,
                                    textAlign: 'center',
                                    cursor: 'text',
                                    outline: 'none',
                                    minWidth: '100px',
                                    padding: '4px 12px',
                                }"
                            ></div>

                            {{-- Subtitle --}}
                            <div
                                x-show="showSubtitle"
                                :style="{
                                    fontSize: '14px',
                                    color: textColor,
                                    opacity: '0.5',
                                    marginTop: '-16px',
                                    fontWeight: '400',
                                }"
                            >
                                {{ count($participants) }} participantes
                            </div>

                            {{-- Grid --}}
                            <div
                                :style="{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(' + columns + ', 1fr)',
                                    gap: gap + 'px',
                                    width: '100%',
                                    justifyContent: 'center',
                                    justifyItems: 'center',
                                }"
                            >
                                @forelse ($participants as $participant)
                                    <div class="showcase-user-card">
                                        <img
                                            src="{{ $participant['avatar_url'] ?: 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><rect fill="#2a2a4a" width="80" height="80"/><text x="40" y="48" text-anchor="middle" fill="#888" font-size="28">' . mb_strtoupper(mb_substr($participant['global_name'], 0, 1)) . '</text></svg>') }}"
                                            alt="{{ $participant['global_name'] }}"
                                            loading="lazy"
                                            crossorigin="anonymous"
                                            :style="{
                                                width: avatarSize + 'px',
                                                height: avatarSize + 'px',
                                                border:
                                                    avatarBorder > 0
                                                        ? avatarBorder + 'px solid ' + avatarBorderColor
                                                        : 'none',
                                            }"
                                        />
                                        <span
                                            x-show="showName"
                                            :style="{
                                                fontWeight: '600',
                                                color: textColor,
                                                lineHeight: '1.2',
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                whiteSpace: 'nowrap',
                                                maxWidth: '100%',
                                                fontSize: fontName + 'px',
                                            }"
                                            >{{ $participant['global_name'] }}</span
                                        >
                                        <span
                                            x-show="showUsername"
                                            :style="{
                                                color: textColor,
                                                opacity: '0.4',
                                                lineHeight: '1.2',
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                whiteSpace: 'nowrap',
                                                maxWidth: '100%',
                                                fontSize: fontUser + 'px',
                                            }"
                                            >{{ '@' . $participant['username'] }}</span
                                        >
                                        <span
                                            x-show="showMessages"
                                            :style="{
                                                color: '#7c3aed',
                                                fontWeight: '600',
                                                lineHeight: '1.2',
                                                fontVariantNumeric: 'tabular-nums',
                                                fontSize: fontMsgs + 'px',
                                            }"
                                            >{{ $participant['total_messages'] }} msgs</span
                                        >
                                    </div>
                                @empty
                                    <div class="col-span-full py-16 text-center text-sm text-gray-400">
                                        Nenhum participante encontrado neste canal/período.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Controls Panel --}}
                <div
                    class="showcase-ctrl w-full shrink-0 space-y-4 overflow-y-auto lg:max-h-[calc(100vh-220px)] lg:w-80"
                >
                    {{-- Participant Count --}}
                    <div class="flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 dark:bg-white/5">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Participantes:</span>
                        <strong class="text-lg text-purple-600 dark:text-purple-400">{{ count($participants) }}</strong>
                    </div>

                    {{-- Layout --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Layout
                        </h3>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Largura</label>
                            <input type="range" x-model.number="width" min="300" max="1920" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="width"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Colunas</label>
                            <input type="range" x-model.number="columns" min="2" max="20" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="columns"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Gap</label>
                            <input type="range" x-model.number="gap" min="4" max="48" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="gap"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Padding</label>
                            <input type="range" x-model.number="padding" min="0" max="80" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="padding"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Border Radius</label>
                            <input type="range" x-model.number="borderRadius" min="0" max="48" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="borderRadius"></span>
                        </div>

                        <div class="pt-1">
                            <span class="text-[11px] text-gray-400">Presets</span>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <button
                                    x-on:click="applyPreset('story')"
                                    class="rounded border border-gray-300 px-2 py-1 text-[10px] text-gray-500 transition hover:border-purple-500 hover:text-purple-500 dark:border-gray-600 dark:text-gray-400 dark:hover:border-purple-400 dark:hover:text-purple-400"
                                >
                                    Story 9:16
                                </button>
                                <button
                                    x-on:click="applyPreset('post')"
                                    class="rounded border border-gray-300 px-2 py-1 text-[10px] text-gray-500 transition hover:border-purple-500 hover:text-purple-500 dark:border-gray-600 dark:text-gray-400 dark:hover:border-purple-400 dark:hover:text-purple-400"
                                >
                                    Post 1:1
                                </button>
                                <button
                                    x-on:click="applyPreset('wide')"
                                    class="rounded border border-gray-300 px-2 py-1 text-[10px] text-gray-500 transition hover:border-purple-500 hover:text-purple-500 dark:border-gray-600 dark:text-gray-400 dark:hover:border-purple-400 dark:hover:text-purple-400"
                                >
                                    Wide 16:9
                                </button>
                                <button
                                    x-on:click="applyPreset('banner')"
                                    class="rounded border border-gray-300 px-2 py-1 text-[10px] text-gray-500 transition hover:border-purple-500 hover:text-purple-500 dark:border-gray-600 dark:text-gray-400 dark:hover:border-purple-400 dark:hover:text-purple-400"
                                >
                                    Banner
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Avatar
                        </h3>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Tamanho</label>
                            <input type="range" x-model.number="avatarSize" min="24" max="128" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="avatarSize"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Borda</label>
                            <input type="range" x-model.number="avatarBorder" min="0" max="6" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="avatarBorder"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="text-gray-500 dark:text-gray-400">Cor da borda</label>
                            <input
                                type="color"
                                x-model="avatarBorderColor"
                                class="h-6 w-8 cursor-pointer rounded border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                    </div>

                    {{-- Typography --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Tipografia
                        </h3>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Fonte nome</label>
                            <input type="range" x-model.number="fontName" min="8" max="24" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="fontName"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Fonte username</label>
                            <input type="range" x-model.number="fontUser" min="6" max="20" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="fontUser"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Fonte msgs</label>
                            <input type="range" x-model.number="fontMsgs" min="6" max="20" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="fontMsgs"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Fonte título</label>
                            <input type="range" x-model.number="fontTitle" min="12" max="48" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="fontTitle"></span>
                        </div>
                    </div>

                    {{-- Visibility --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Visibilidade
                        </h3>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Título</label>
                            <flux:switch x-model="showTitle" />
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Subtítulo (contagem)</label>
                            <flux:switch x-model="showSubtitle" />
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Nome</label>
                            <flux:switch x-model="showName" />
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Username</label>
                            <flux:switch x-model="showUsername" />
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Mensagens</label>
                            <flux:switch x-model="showMessages" />
                        </div>
                        <div class="flex items-center justify-between py-1">
                            <label class="text-xs text-gray-500 dark:text-gray-400">Fundo</label>
                            <flux:switch x-model="showBackground" />
                        </div>
                    </div>

                    {{-- Background --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Fundo
                        </h3>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Opacidade</label>
                            <input type="range" x-model.number="opacity" min="0" max="100" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="opacity + '%'"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="text-gray-500 dark:text-gray-400">Cor do fundo</label>
                            <input
                                type="color"
                                x-model="bgColor"
                                class="h-6 w-8 cursor-pointer rounded border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="text-gray-500 dark:text-gray-400">Cor do texto</label>
                            <input
                                type="color"
                                x-model="textColor"
                                class="h-6 w-8 cursor-pointer rounded border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                    </div>

                    {{-- Export --}}
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                        <h3 class="text-xs font-semibold tracking-wider text-purple-600 uppercase dark:text-purple-400">
                            Export
                        </h3>
                        <div class="flex items-center gap-2">
                            <label class="text-gray-500 dark:text-gray-400">Escala</label>
                            <input type="range" x-model.number="exportScale" min="1" max="4" />
                            <span class="val text-gray-700 dark:text-gray-300" x-text="exportScale + 'x'"></span>
                        </div>
                        <flux:button
                            x-on:click="exportPNG()"
                            variant="primary"
                            class="w-full"
                            x-bind:disabled="exporting"
                        >
                            <span x-show="!exporting">Exportar PNG</span>
                            <span x-show="exporting" x-cloak>Exportando...</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center gap-4 rounded-xl bg-gray-50 py-24 dark:bg-white/5">
                <x-heroicon-o-camera class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Insira o Channel ID e o período, depois clique em <strong>Carregar Participantes</strong>.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
