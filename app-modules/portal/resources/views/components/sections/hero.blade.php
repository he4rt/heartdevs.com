<section class="hp-section">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div>
                <x-portal::headline size="2xl" :keywords="['potencial']">
                    <x-slot:badge>
                        <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5" />
                        Comunidade Open Source
                    </x-slot>

                    <x-slot:title>Desenvolva seu potencial na comunidade</x-slot>

                    <x-slot:description>
                        Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais
                        através de projetos, mentorias e networking.
                    </x-slot>
                    <x-slot:actions>
                        <x-portal::button icon="heroicon-s-chevron-right">Começar agora</x-portal::button>

                        <x-portal::button icon="heroicon-s-chevron-right" variant="outline">
                            Explorar projetos
                        </x-portal::button>
                    </x-slot>
                </x-portal::headline>
            </div>
            <div>Image</div>
        </div>
    </div>
</section>
