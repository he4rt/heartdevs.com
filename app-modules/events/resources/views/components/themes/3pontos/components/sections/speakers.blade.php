@props([
    'event',
])
<section class="hp-section relative" id="speakers">
    <div
        class="absolute bottom-0 left-0 z-1 flex origin-top rotate-90 justify-start sm:-translate-x-[20%] sm:translate-y-32 lg:-translate-x-[5%] lg:translate-y-24 lg:rotate-0 xl:translate-y-0"
    >
        <img
            src="{{ asset('images/3pontos/logo-chain.png') }}"
            alt=""
            class="hidden h-auto w-full object-contain sm:block sm:max-w-[60%]"
        />
    </div>

    <div class="hp-container relative z-10">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Palestrantes</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Palestrantes do evento</x-slot>
            </x-he4rt::headline>
        </div>

        <div
            x-data="{ visible: false }"
            x-intersect.once="visible = true"
            class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 lg:grid-cols-4"
        >
            <x-he4rt::animate-block>
                <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                    <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                        <div class="flex flex-col items-center gap-2">
                            <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                            <x-he4rt::heading level="3" size="xs">Juliana Gaioso</x-he4rt::heading>
                            <x-he4rt::text class="text-center" size="sm">DevSec</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:description class="pb-4 text-center">
                        Por mais de quinze anos, solucionando problemas na indústria através de automação, IoT e
                        desenvolvimento de software. Atualmente focada em solucionar problemas de software de segurança.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                    <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                        <div class="flex flex-col items-center gap-2">
                            <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                            <x-he4rt::heading level="3" size="xs">Tatiana Barros</x-he4rt::heading>
                            <x-he4rt::text class="text-center" size="sm">Technology Evangelist</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:description class="pb-4 text-center">
                        Há mais de uma década unindo tecnologia, criatividade e impacto social. Evangelista de
                        Tecnologia focada em fortalecer comunidades dev e ampliar o acesso à educação tecnológica por
                        meio de workshops, mentorias e iniciativas premiadas.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                    <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                        <div class="flex flex-col items-center gap-2">
                            <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/6912596?v=4" />
                            <x-he4rt::heading level="3" size="xs">Daniel Reis</x-he4rt::heading>
                            <x-he4rt::text class="text-center" size="sm">
                                Tech Lead & Fundador da He4rt Developers
                            </x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:description class="pb-4 text-center">
                        Linha de frente na criação de softwares e fortalecendo comunidades dev. DevRel focado em
                        conteúdo técnico, live coding e educação, sempre impulsionando novos talentos. Fundador da He4rt
                        Developers e apaixonado por ensinar, programar e construir espaços onde desenvolvedores crescem
                        juntos.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="hover:border-b-outline-light border-b-8">
                    <x-slot:header class="flex flex-col items-center justify-center border-none pb-0">
                        <div class="flex flex-col items-center gap-2">
                            <x-he4rt::avatar size="2xl" src="https://avatars.githubusercontent.com/u/85951158?v=4" />
                            <x-he4rt::heading level="3" size="xs">Gabriel Vieira</x-he4rt::heading>
                            <x-he4rt::text class="text-center" size="sm">Product Designer</x-he4rt::text>
                        </div>
                    </x-slot>
                    <x-slot:description class="pb-4 text-center">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla vitae viverra nisi. Morbi at
                        lorem facilisis enim eleifend sagittis at id massa.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>
        </div>
    </div>
</section>
