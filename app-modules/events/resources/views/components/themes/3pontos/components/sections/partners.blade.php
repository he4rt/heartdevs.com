<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Parceiros</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Nossos parceiros</x-slot>
                <x-slot:description>
                    Parceiros que se unem a nós para construir impacto de verdade, com valor e propósito.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div class="overflow-hidden mask-r-from-95% mask-r-to-99% mask-l-from-95% mask-l-to-99%">
            <div class="animate-infinite-scroll flex gap-6">
                @for ($i = 0; $i < 2; $i++)
                    <p class="w-40 shrink-0 hover:scale-105">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0">Teste</p>
                    <p class="w-40 shrink-0 transition-transform duration-300 ease-in-out hover:scale-105">Bom dia</p>
                @endfor
            </div>
        </div>
    </div>
</section>
