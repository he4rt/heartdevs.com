<section class="hp-section">
    <div class="hp-container">
        <x-portal::headline align="center" size="md" :keywords="['membros']">
            <x-slot:badge>
                <x-filament::icon icon="heroicon-o-chat-bubble-oval-left-ellipsis" class="h-5 w-5" />
                Depoimentos
            </x-slot>

            <x-slot:title>O que dizem nossos membros</x-slot>

            <x-slot:description>
                Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa comunidade.
            </x-slot>
        </x-portal::headline>

        <div class="grid w-full grid-cols-1 items-center justify-center sm:grid-cols-3">
            <p class="text-center">card 1</p>
            <p class="text-center">card 2</p>
            <p class="text-center">card 3</p>
        </div>
    </div>
</section>
