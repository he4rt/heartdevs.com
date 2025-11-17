<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center" :keywords="['Presencial?']">
                <x-slot:badge>
                    <x-3pontos::section-title>Section name</x-3pontos::section-title>
                </x-slot>
                <x-slot:title>O que esperar da 3pontos Meetup Presencial?</x-slot>
                <x-slot:description>
                    O Meetup da He4rt é um evento que reúne os nosso membros presencialmente e virtualmente pra
                    trocarmos conhecimentos, fazer networking e unir cada vez mais nossa comunidade.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-2 lg:gap-y-12">
            <x-he4rt::card class="lg:flex-row">
                <x-slot:icon class="w-fit items-center justify-center p-2">
                    <x-3pontos::icon />
                </x-slot>
                <x-slot:title>Networking</x-slot>
                <x-slot:description>
                    Teremos na festa o estande de acessórios da Punky Kills & de roupas da Lobo Bobo! Você curte a festa
                    já num lookinho renovado!
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="lg:flex-row">
                <x-slot:icon class="w-fit items-center justify-center p-2">
                    <x-3pontos::icon />
                </x-slot>
                <x-slot:title>Brindes</x-slot>
                <x-slot:description>
                    Teremos na festa o estande de acessórios da Punky Kills & de roupas da Lobo Bobo! Você curte a festa
                    já num lookinho renovado!
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="lg:flex-row">
                <x-slot:icon class="w-fit items-center justify-center p-2">
                    <x-3pontos::icon />
                </x-slot>
                <x-slot:title>CoffeeBreak</x-slot>
                <x-slot:description>
                    Teremos na festa o estande de acessórios da Punky Kills & de roupas da Lobo Bobo! Você curte a festa
                    já num lookinho renovado!
                </x-slot>
            </x-he4rt::card>

            <x-he4rt::card class="lg:flex-row">
                <x-slot:icon class="w-fit items-center justify-center p-2">
                    <x-3pontos::icon />
                </x-slot>
                <x-slot:title>Conteúdos Exclusivos</x-slot>
                <x-slot:description>
                    Teremos na festa o estande de acessórios da Punky Kills & de roupas da Lobo Bobo! Você curte a festa
                    já num lookinho renovado!
                </x-slot>
            </x-he4rt::card>
        </div>
    </div>
</section>
