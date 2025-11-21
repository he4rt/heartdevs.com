<section
    class="hp-section bg-elevation-01dp border-outline-dark relative min-h-0 overflow-hidden border-t border-b"
    id="about"
>
    <div class="absolute right-0 bottom-0 z-1 h-1/2 w-1/2 translate-x-1/2 lg:-translate-y-[110%]">
        <img src="{{ asset('images/3pontos/3pontos-ball.svg') }}" alt="" class="h-auto w-full object-cover" />
    </div>

    <div class="hp-container relative z-10">
        <div>
            <x-he4rt::headline :keywords="['3', 'Pontos']">
                <x-slot:badge>
                    <x-he4rt::section-title>Sobre nós</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Um pouco sobre à 3 Pontos</x-slot>
                <x-slot:description>
                    Somos o ecossistema que une solução e conhecimento em um único lugar Aceleramos sua empresa.
                    Fortalecemos sua carreira. Conectando empresas e startups inovadoras a talentos excepcionais,
                    acelerando soluções reais e transformando ideias em impacto.
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button>Saiba mais</x-he4rt::button>
                </x-slot>
            </x-he4rt::headline>
        </div>
    </div>
</section>
