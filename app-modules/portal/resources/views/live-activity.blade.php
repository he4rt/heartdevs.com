<div
    wire:poll.10s="pulse"
    aria-live="polite"
    class="flex h-screen w-full flex-col bg-black"
    x-data="{ notLinked: false }"
    x-on:discord-activity:not-linked.window="notLinked = true"
>
    @vite('app-modules/portal/resources/js/live-player.js')

    <div
        x-show="notLinked"
        x-cloak
        class="flex items-center justify-between gap-3 bg-amber-500/10 px-4 py-2 text-xs text-amber-300"
    >
        <span>Vincule sua conta Discord em heartdevs.com para participar do chat.</span>
        <a href="{{ route('filament.app.pages.profile') }}" target="_blank" rel="noopener" class="font-semibold underline">
            Vincular conta
        </a>
    </div>

    @if ($live === null)
        <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-6 text-center">
            <p class="text-sm font-semibold text-white">Nenhuma live no ar agora</p>
            <p class="max-w-sm text-xs text-white/60">
                Esta página atualiza sozinha quando a transmissão começar.
            </p>
        </div>
    @else
        <div x-data="{ chatCollapsed: false }" class="flex h-full min-h-0 w-full">
            <div class="relative h-full min-w-0 flex-1 overflow-hidden bg-black">
                @if ($status->onAir)
                    <video
                        data-live-player
                        data-hls-url="{{ route('live.discord-activity-hls', ['path' => 'index.m3u8']) }}"
                        data-live-channel="{{ $live->id }}"
                        controls
                        autoplay
                        muted
                        playsinline
                        class="h-full w-full bg-black object-contain"
                    ></video>
                @endif

                <div
                    data-live-waiting
                    data-live-channel="{{ $live->id }}"
                    @class([
                        'absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black p-6 text-center',
                        'hidden' => $status->onAir,
                    ])
                >
                    <p class="text-sm font-semibold text-white">Aguardando sinal</p>
                    <p class="max-w-sm text-xs text-white/60">
                        A transmissão volta sozinha assim que o sinal for restabelecido.
                    </p>
                </div>

                <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-3 bg-gradient-to-b from-black/70 via-black/20 to-transparent p-3">
                    <div class="pointer-events-auto flex items-center gap-2">
                        @if ($status->onAir)
                            <span class="flex w-fit items-center gap-1.5 rounded-full border border-red-500/40 bg-red-500/10 px-2 py-1 text-[11px] font-semibold text-red-400">
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                </span>
                                Ao vivo
                            </span>
                        @endif

                        @if ($viewers > 0)
                            <span class="rounded-full bg-black/40 px-2 py-1 text-[11px] font-medium text-white">{{ $viewers }} assistindo</span>
                        @endif
                    </div>
                </div>
            </div>

            <div
                class="h-full shrink-0 overflow-hidden"
                :class="chatCollapsed ? 'w-10' : 'w-[18rem]'"
            >
                <div x-show="!chatCollapsed" class="h-full min-h-0 overflow-hidden">
                    <livewire:portal.live-chat :live-id="$live->id" :key="$live->id" />
                </div>

                <button
                    x-show="chatCollapsed"
                    x-cloak
                    @click="chatCollapsed = false"
                    type="button"
                    class="flex h-full w-full flex-col items-center justify-center gap-2 border-l border-white/10 bg-black/60 py-4 text-white/60 hover:text-white"
                >
                    <x-filament::icon icon="heroicon-o-chevron-left" class="h-4 w-4" />
                    <span class="text-[10px] font-semibold [writing-mode:vertical-rl]">Chat</span>
                </button>
            </div>
        </div>
    @endif
</div>
