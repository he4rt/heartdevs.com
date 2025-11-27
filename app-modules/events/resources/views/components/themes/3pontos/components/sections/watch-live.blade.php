<section class="hp-section relative" id="watch-live">
    <div class="absolute inset-0">
        <img
            src="{{ asset('images/3pontos/grids-horizontal.svg') }}"
            alt="bg-watch-live"
            class="h-full w-full object-cover"
        />
    </div>
    <div class="hp-container relative">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Ao vivo</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Como assistir ao vivo?</x-slot>
                <x-slot:description>
                    Caso você não consiga ir até o evento presencial, teremos uma live AO VIVO durante o evento!
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div
            class="bg-elevation-01dp border-outline-dark relative mx-auto aspect-video w-full max-w-5xl overflow-hidden rounded-xl border shadow-lg"
        >
            <iframe
                class="absolute inset-0 h-full w-full"
                loading="lazy"
                src="http://www.youtube.com/embed/dQw4w9WgXcQ"
                title="YouTube video player"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</section>
