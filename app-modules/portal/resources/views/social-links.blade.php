<div class="relative min-h-screen overflow-hidden">
    <style>
        .links-ecg {
            width: 100%;
            max-width: 320px;
            height: 48px;
            display: block;
            margin: 12px auto 28px;
        }
        .links-ecg path {
            fill: none;
            stroke: var(--primary, #782bf1);
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
            filter: drop-shadow(0 0 7px rgba(120, 43, 241, 0.85));
            stroke-dasharray: 1500;
            stroke-dashoffset: 1500;
            animation: links-ecg-draw 1.8s 0.3s ease-out forwards;
        }
        @keyframes links-ecg-draw {
            to {
                stroke-dashoffset: 0;
            }
        }
        .link-card {
            transition:
                border-color 0.2s,
                color 0.2s,
                transform 0.2s,
                background-color 0.2s;
        }
        .link-card:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
            background-color: color-mix(in srgb, var(--accent) 8%, transparent);
        }
        .link-card:hover .link-arrow {
            transform: translateX(4px);
        }
        @media (prefers-reduced-motion: reduce) {
            .links-ecg path {
                animation: none;
                stroke-dashoffset: 0;
            }
            .link-card,
            .link-card:hover {
                transform: none;
            }
        }
    </style>

    <div class="mx-auto flex w-full max-w-[480px] flex-col items-center px-6 py-16">
        <x-portal::logo size="lg" />

        <p class="text-text-medium mt-4 text-center text-sm">Conecte-se com a comunidade He4rt Devs</p>

        <svg class="links-ecg" viewBox="0 0 560 60" preserveAspectRatio="none" aria-hidden="true">
            <path
                d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
            />
        </svg>

        <nav class="flex w-full flex-col gap-3" aria-label="Redes sociais da He4rt">
            @foreach ($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="--accent: {{ $link->accent }}"
                    class="link-card border-outline-low bg-elevation-01dp flex items-center gap-4 rounded-xl border px-5 py-4 text-white"
                >
                    <x-filament::icon :icon="$link->icon" class="h-6 w-6 shrink-0" />
                    <span class="flex-1 text-left font-medium">{{ $link->label }}</span>
                    <x-filament::icon
                        icon="heroicon-s-arrow-up-right"
                        class="link-arrow h-4 w-4 shrink-0 transition-transform"
                    />
                </a>
            @endforeach
        </nav>
    </div>
</div>
