<section class="hp-section" id="projects">
    <div class="hp-container">
        <x-portal::headline align="center" size="md" :keywords="['prática']">
            <x-slot:badge>
                <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                Nossos projetos
            </x-slot>

            <x-slot:title>Aprenda na prática</x-slot>

            <x-slot:description>
                Contribua com projetos open source e desenvolva habilidades reais enquanto constrói seu portfólio.
            </x-slot>
        </x-portal::headline>

        <div class="grid w-full grid-cols-1 items-center justify-center gap-8 sm:grid-cols-3 lg:gap-16">
            <x-portal::card>
                <x-slot:title>4Noobs</x-slot>
                <x-slot:description>Repositórios de conteúdos sobre diversas tecnologias.</x-slot>
                <x-slot:tags>
                    <x-portal::tag>Documentação</x-portal::tag>
                    <x-portal::tag>Documentação</x-portal::tag>
                </x-slot>
                <x-slot:actions>
                    <x-portal::button block>Ver projeto</x-portal::button>
                </x-slot>
            </x-portal::card>

            <x-portal::card>
                <x-slot:title>He4rtLabs</x-slot>
                <x-slot:description>
                    Projetos práticos desenvolvidos pela comunidade para aprendizado.
                </x-slot>
                <x-slot:tags>
                    <x-portal::tag>Documentação</x-portal::tag>
                    <x-portal::tag>Documentação</x-portal::tag>
                </x-slot>
                <x-slot:actions>
                    <x-portal::button block>Ver projeto</x-portal::button>
                </x-slot>
            </x-portal::card>

            <x-portal::card>
                <x-slot:title>Desafios de Código</x-slot>
                <x-slot:description>
                    Desafios semanais para praticar suas habilidades de programação.
                </x-slot>
                <x-slot:tags>
                    <x-portal::tag>Documentação</x-portal::tag>
                    <x-portal::tag>Documentação</x-portal::tag>
                </x-slot>
                <x-slot:actions>
                    <x-portal::button block>Ver projeto</x-portal::button>
                </x-slot>
            </x-portal::card>
        </div>
    </div>
</section>
