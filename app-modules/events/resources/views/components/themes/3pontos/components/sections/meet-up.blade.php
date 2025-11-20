<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center" :keywords="['Futuro?']">
                <x-slot:badge>
                    <x-he4rt::section-title>Propósito</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>
                    O que esperar do evento 3 Pontos Star: O Lançamento da Comunidade que Acelera o Futuro?
                </x-slot>
                <x-slot:description>
                    O 3 Pontos Start é o marco zero da nossa comunidade. É a sua chance de se aprofundar no ecossistema
                    3 Pontos - a aceleradora que une negócios e tecnologia e se conectar com profissionais que estão no
                    topo do mercado.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div
            x-data="{ visible: false }"
            x-intersect.threshold.20.once="visible = true"
            class="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-2 lg:gap-y-12"
        >
            <x-he4rt::animate-block>
                <x-he4rt::card class="lg:flex-row">
                    <x-slot:icon class="w-fit items-center justify-center p-2">
                        <x-he4rt::icon />
                    </x-slot>
                    <x-slot:title>Conteúdos Exclusivos</x-slot>
                    <x-slot:description>
                        Insights de parceiros e líderes sobre Fintech, Inovação e Carreira Dev.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="lg:flex-row">
                    <x-slot:icon class="w-fit items-center justify-center p-2">
                        <x-he4rt::icon />
                    </x-slot>
                    <x-slot:title>Networking</x-slot>
                    <x-slot:description>
                        Interaja ao vivo com convidados e a comunidade He4rt. 5 horas de conteúdo, uma imersão completa
                        para quem quer acelerar o mercado.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="lg:flex-row">
                    <x-slot:icon class="w-fit items-center justify-center p-2">
                        <x-he4rt::icon />
                    </x-slot>
                    <x-slot:title>Missão Social</x-slot>
                    <x-slot:description>
                        Seu código pode transforma vidas, acreditamos que a tecnologia tem o poder de transformar, e
                        isso começa pela comunidade.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block>
                <x-he4rt::card class="lg:flex-row">
                    <x-slot:icon class="w-fit items-center justify-center p-2">
                        <x-he4rt::icon />
                    </x-slot>
                    <x-slot:title>Brindes</x-slot>
                    <x-slot:description>
                        Quem faz parte, ganha mais: sorteio de brindes exclusivo para nossa comunidade!
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>
        </div>
    </div>
</section>
