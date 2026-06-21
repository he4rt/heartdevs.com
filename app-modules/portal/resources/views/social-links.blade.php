<div class="relative">
    <style>
        /* Animated logo as a faint, fixed background watermark (out of flow → never adds height). */
        .links-logo-bg {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            opacity: 0.5;
            pointer-events: none;
            z-index: 0;
        }
        .links-logo-bg .he4rt-logo {
            width: min(110vh, 150vw);
            max-width: 1100px;
        }

        .d-pill {
            transition:
                border-color 0.2s,
                background-color 0.2s,
                color 0.2s,
                transform 0.2s;
            backdrop-filter: blur(6px);
        }
        .d-pill:hover {
            border-color: var(--accent);
            color: var(--accent);
            background-color: color-mix(in srgb, var(--accent) 12%, rgba(11, 10, 16, 0.6));
            transform: scale(1.03);
        }

        @media (prefers-reduced-motion: reduce) {
            .d-pill,
            .d-pill:hover {
                transform: none;
            }
        }
    </style>

    <div class="links-logo-bg">
        <x-portal::animated-logo />
    </div>

    {{-- Foreground sized to the viewport minus the navbar (~6rem) so the page never scrolls vertically. --}}
    <div
        class="relative z-10 mx-auto flex min-h-[calc(100svh-6rem)] w-full max-w-md flex-col items-center justify-center px-6"
    >
        <p class="text-xs font-semibold tracking-[0.3em] text-white/60 uppercase">He4rt Devs</p>
        <h1 class="mt-3 text-center text-2xl font-bold text-white">Escolha seu canal</h1>

        <nav class="mt-8 flex w-full flex-col gap-3" aria-label="Redes sociais da He4rt">
            @foreach ($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="--accent: {{ $link->accent }}"
                    class="d-pill flex items-center justify-center gap-3 rounded-full border border-white/15 bg-white/5 px-6 py-3.5 font-medium text-white"
                >
                    <x-filament::icon :icon="$link->icon" class="h-5 w-5 shrink-0" />
                    <span>{{ $link->label }}</span>
                </a>
            @endforeach
        </nav>
    </div>
</div>
