<section class="hp-section relative items-start">
    <div class="absolute inset-0 -left-[20%] z-0 h-full w-[150%]">
        <img src="{{ asset('images/3pontos/logo-chain.png') }}" alt="" class="h-full w-full object-cover" />

        <div class="bg-elevation-surface/90 absolute inset-0"></div>
        <div class="from-elevation-surface/80 absolute inset-0 bg-gradient-to-t to-transparent"></div>
    </div>

    <div
        class="pointer-events-none absolute top-1/2 right-0 z-5 hidden w-[600px] translate-x-1/3 -translate-y-1/2 xl:block"
    >
        <img
            src="{{ asset('images/3pontos/logo-rounded.svg') }}"
            alt=""
            class="spin-slow h-auto w-full object-contain"
        />
    </div>

    <div class="hp-container relative z-10 max-w-7xl">
        <x-he4rt::headline>
            <x-slot:badge>
                <x-he4rt::section-title>Section name</x-he4rt::section-title>
            </x-slot>
            <x-slot:title>Participe do primeiro evento presencial da 3Pontos</x-slot>
            <x-slot:description>
                O Meetup da He4rt é um evento que reúne os nosso membros presencialmente e virtualmente pra trocarmos
                conhecimentos, fazer networking e unir cada vez mais nossa comunidade.
            </x-slot>
            <x-slot:actions>
                <x-he4rt::button>Button</x-he4rt::button>
            </x-slot>
        </x-he4rt::headline>
    </div>
</section>
