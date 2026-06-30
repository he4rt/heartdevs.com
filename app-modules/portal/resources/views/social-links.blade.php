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
        .dark .links-logo-bg {
            opacity: 0.5;
        }

        /* Entrance: the logo outline draws itself once, then the content cascades up. */
        @keyframes links-trace-draw {
            to {
                stroke-dashoffset: 0;
            }
        }
        @keyframes links-reveal {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .links-logo-bg .he4rt-logo .trace {
            stroke-dasharray: 3000;
            stroke-dashoffset: 3000;
            animation: links-trace-draw 1.5s ease-out forwards;
        }
        .links-reveal {
            /* `backwards` fill keeps the hover transform working once the entrance ends. */
            animation: links-reveal 0.5s ease-out backwards;
            animation-delay: calc(1100ms + var(--i, 0) * 70ms);
        }

        .d-pill {
            --accent-resolved: var(--accent-light);
            transition:
                border-color 0.2s,
                background-color 0.2s,
                color 0.2s,
                transform 0.2s,
                box-shadow 0.2s;
            border-color: color-mix(in srgb, var(--outline-high) 18%, transparent);
            background-color: color-mix(in srgb, var(--elevation-02dp) 78%, transparent);
            color: var(--text-high);
            backdrop-filter: blur(6px);
            box-shadow: 0 18px 40px color-mix(in srgb, var(--text-dark) 8%, transparent);
        }
        .dark .d-pill {
            --accent-resolved: var(--accent-dark, var(--accent-light));
        }
        .d-pill:hover {
            border-color: var(--accent-resolved);
            color: var(--accent-resolved);
            background-color: color-mix(
                in srgb,
                var(--accent-resolved) 12%,
                color-mix(in srgb, var(--elevation-02dp) 78%, transparent)
            );
            transform: scale(1.03);
            box-shadow: 0 22px 44px color-mix(in srgb, var(--accent-resolved) 16%, transparent);
        }

        @media (prefers-reduced-motion: reduce) {
            .links-logo-bg .he4rt-logo .trace,
            .links-reveal {
                animation: none !important;
            }
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
        <p class="links-reveal text-text-medium text-xs font-semibold tracking-[0.3em] uppercase" style="
                --i: 0;
            ">He4rt Devs</p>
        <h1 class="links-reveal text-text-high mt-3 text-center text-2xl font-bold" style="--i: 1">Escolha seu canal</h1>

        <nav class="mt-8 flex w-full flex-col gap-3" aria-label="Redes sociais da He4rt">
            @foreach ($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="--accent-light: {{ $link->accent }}; --accent-dark: {{ $link->accentDark ?? $link->accent }}; --i: {{ $loop->index + 2 }}"
                    class="d-pill links-reveal flex items-center justify-center gap-3 rounded-full border px-6 py-3.5 font-medium"
                >
                    <x-filament::icon :icon="$link->icon" class="h-5 w-5 shrink-0" />
                    <span>{{ $link->label }}</span>
                </a>
            @endforeach
        </nav>
    </div>
</div>
