<section class="hp-section">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-16">
            <div>cards</div>
            <div>
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
