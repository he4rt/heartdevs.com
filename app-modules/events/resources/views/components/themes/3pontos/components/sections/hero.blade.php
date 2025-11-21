<section class="hp-section relative items-start" id="hero">
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

    <div class="hp-container relative z-10 items-start">
        <x-he4rt::headline class="mx-0 max-w-5xl">
            <x-slot:badge>
                <x-he4rt::section-title>3 Pontos Start</x-he4rt::section-title>
            </x-slot>
            <x-slot:title>Participe do primeiro evento presencial da 3Pontos</x-slot>
            <x-slot:description>
                Um evento híbrido de 5 horas, em parceria com a He4rt, com palestras exclusivas, networking e uma missão
                social que transforma.
            </x-slot>
            <x-slot:actions>
                <x-he4rt::button>Faça sua inscrição</x-he4rt::button>
            </x-slot>
        </x-he4rt::headline>

        <div
            x-data="{ visible: false }"
            x-intersect.threshold.20.once="visible = true"
            class="bg-elevation-surface/32 border-outline-dark/60 grid w-full grid-cols-1 gap-8 rounded-xl border p-8 backdrop-blur-md lg:grid-cols-3"
        >
            <x-he4rt::animate-block type="fade-right">
                <x-he4rt::card :interactive="false">
                    <x-slot:icon>
                        <div class="flex items-center gap-2">
                            <x-he4rt::icon icon="heroicon-o-clock" class="text-text-medium bg-transparent p-0" />
                            <x-he4rt::text size="sm" class="font-semibold">Horário</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:title>15h ~ 20h - Duração: 5h</x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block delay="100" type="fade-right">
                <x-he4rt::card :interactive="false">
                    <x-slot:icon>
                        <div class="flex items-center gap-2">
                            <x-he4rt::icon icon="heroicon-o-tv" class="text-text-medium bg-transparent p-0" />
                            <x-he4rt::text size="sm" class="font-semibold">Transmissão ao vivo na Twitch</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:title>
                        <a href="/">Clique aqui para assistir ao vivo</a>
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block delay="200" type="fade-right">
                <x-he4rt::card :interactive="false">
                    <x-slot:icon>
                        <div class="flex items-center gap-2">
                            <x-he4rt::icon icon="heroicon-o-tv" class="text-text-medium bg-transparent p-0" />
                            <x-he4rt::text size="sm" class="font-semibold">Vagas limitadas</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:title>50 inscrições com camisetas exclusivas</x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>
        </div>
    </div>
</section>
