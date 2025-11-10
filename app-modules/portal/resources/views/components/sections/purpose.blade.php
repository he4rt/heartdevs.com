<section class="hp-section">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-16">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                <x-portal::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary-purple/8 border-primary-purple/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-code-bracket" class="text-primary-purple h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Comunidade</x-slot>
                    <x-slot:description>
                        Mais de 2.000 membros ativos compartilhando conhecimento.
                    </x-slot>
                </x-portal::card>
                <x-portal::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary-purple/8 border-primary-purple/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-code-bracket" class="text-primary-purple h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Comunidade</x-slot>
                    <x-slot:description>
                        Workshops, lives e encontros virtuais com especialistas.
                    </x-slot>
                </x-portal::card>
                <x-portal::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary-purple/8 border-primary-purple/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-code-bracket" class="text-primary-purple h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Comunidade</x-slot>
                    <x-slot:description>
                        Dezenas de projetos open source para contribuir e aprender.
                    </x-slot>
                </x-portal::card>
                <x-portal::card>
                    <x-slot:icon>
                        <div
                            class="bg-primary-purple/8 border-primary-purple/16 flex w-fit items-center justify-center rounded-lg border p-2"
                        >
                            <x-filament::icon icon="heroicon-o-code-bracket" class="text-primary-purple h-4 w-4" />
                        </div>
                    </x-slot>
                    <x-slot:title>Comunidade</x-slot>
                    <x-slot:description>
                        Orientação personalizada para acelerar seu desenvolvimento.
                    </x-slot>
                </x-portal::card>
            </div>
            <div class="flex flex-col items-center justify-center">
                <x-portal::headline size="md" :keywords="['jornada']">
                    <x-slot:badge>
                        <x-filament::icon icon="heroicon-o-cursor-arrow-ripple" class="h-5 w-5" />
                        Nossa missão
                    </x-slot>

                    <x-slot:title>Transformando a jornada em desenvolvimento</x-slot>

                    <x-slot:description>
                        Nascemos da necessidade de unir pessoas que compartilham do mesmo propósito
                        <span class="text-text-high font-bold">aprender desenvolvimento</span>
                        e
                        <span class="text-text-high font-bold">ajudar desenvolvedores a crescerem juntos.</span>

                        <br />

                        Nossa comunidade é formada por desenvolvedores de todos os níveis, desde iniciantes até
                        profissionais experientes, que colaboram em projetos open source, compartilham conhecimento e
                        criam oportunidades.
                    </x-slot>
                    <x-slot:actions>
                        <x-portal::button icon="heroicon-s-chevron-right" variant="outline">
                            Conheça nossa história
                        </x-portal::button>
                    </x-slot>
                </x-portal::headline>
            </div>
        </div>
    </div>
</section>
