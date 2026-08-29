<main class="hp-page py-16">
    @vite('app-modules/portal/resources/js/live-player.js')

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6" @if (! $status->onAir) wire:poll.10s @endif>
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-text-high text-2xl font-bold text-balance sm:text-3xl">Live da He4rt</h1>

            @if ($status->onAir)
                <span class="flex w-fit items-center gap-2 rounded-full border border-red-500/40 bg-red-500/10 px-4 py-2 text-xs font-semibold text-red-500 sm:text-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                    </span>
                    Ao vivo
                </span>
            @endif
        </div>

        @if ($status->onAir)
            <video
                data-live-player
                data-hls-url="{{ config('live.hls_url') }}"
                controls
                autoplay
                muted
                playsinline
                class="aspect-video w-full rounded-xl bg-black shadow-lg"
            ></video>
        @else
            <div class="border-outline-low bg-elevation-01dp flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-12 text-center">
                <p class="text-text-high text-sm font-semibold">Nenhuma live no ar agora</p>
                <p class="text-text-medium max-w-sm text-xs">
                    Esta página atualiza sozinha quando a transmissão começar.
                </p>
            </div>
        @endif
    </div>
</main>
