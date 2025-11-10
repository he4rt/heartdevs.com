<section class="hp-section">
    <div class="hp-container">
        <x-portal::headline align="center" size="md" :keywords="['fodas']">
            <x-slot:badge>
                <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5" />
                Nossos artigos
            </x-slot>

            <x-slot:title>Artigos fodas da comunidade</x-slot>

            <x-slot:description>
                Contribua com projetos open source e desenvolva habilidades reais enquanto constrói seu portfólio
            </x-slot>
        </x-portal::headline>

        <div class="grid w-full grid-cols-1 items-center justify-center sm:grid-cols-3">
            <p class="text-center">card 1</p>
            <p class="text-center">card 2</p>
            <p class="text-center">card 3</p>
            <p class="text-center">card 4</p>
            <p class="text-center">card 5</p>
            <p class="text-center">card 6</p>
        </div>
    </div>
</section>
