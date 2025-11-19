<section class="hp-section relative">
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

        <div class="bg-elevation-01dp border-outline-dark flex h-full w-full max-w-5xl rounded-xl border"></div>
    </div>
</section>
