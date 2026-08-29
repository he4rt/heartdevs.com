<main class="hp-page py-16">
    @vite('app-modules/portal/resources/js/live-player.js')

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6" aria-live="polite" wire:poll.10s="pulse">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="text-text-high text-2xl font-bold text-balance sm:text-3xl">
                    {{ $live?->title ?? 'Live da He4rt' }}
                </h1>

                @if ($live?->description)
                    <p class="text-text-medium max-w-2xl text-sm">{{ $live->description }}</p>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if ($viewers > 0)
                    <span class="text-text-medium text-xs font-medium sm:text-sm">{{ $viewers }} assistindo</span>
                @endif

                @if ($live !== null && $status->onAir)
                    <span class="flex w-fit items-center gap-2 rounded-full border border-red-500/40 bg-red-500/10 px-4 py-2 text-xs font-semibold text-red-500 sm:text-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        </span>
                        Ao vivo
                    </span>
                @endif
            </div>
        </div>

        @if ($live === null)
            <div class="border-outline-low bg-elevation-01dp flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-12 text-center">
                <p class="text-text-high text-sm font-semibold">Nenhuma live no ar agora</p>
                <p class="text-text-medium max-w-sm text-xs">
                    Esta página atualiza sozinha quando a transmissão começar.
                </p>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <div class="flex flex-col gap-4">
                    @if ($status->onAir)
                        <video
                            data-live-player
                            data-hls-url="{{ config('live.hls_url') }}"
                            data-live-channel="{{ $live->id }}"
                            controls
                            autoplay
                            muted
                            playsinline
                            class="aspect-video w-full rounded-xl bg-black shadow-lg"
                        ></video>
                    @endif

                    <div
                        data-live-waiting
                        data-live-channel="{{ $live->id }}"
                        @class([
                            'border-outline-low bg-elevation-01dp flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-12 text-center',
                            'hidden' => $status->onAir,
                        ])
                    >
                        <p class="text-text-high text-sm font-semibold">Aguardando sinal</p>
                        <p class="text-text-medium max-w-sm text-xs">
                            A transmissão volta sozinha assim que o sinal for restabelecido.
                        </p>
                    </div>
                </div>

                <livewire:portal.live-chat :live-id="$live->id" :key="$live->id" />
            </div>
        @endif
    </div>
</main>
