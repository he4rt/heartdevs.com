<section class="hp-section relative">
    <div
        class="absolute bottom-0 left-0 z-1 flex origin-top rotate-90 justify-start sm:-translate-x-[20%] sm:translate-y-32 lg:-translate-x-[5%] lg:translate-y-24 lg:rotate-0"
    >
        <img
            src="{{ asset('images/3pontos/logo-chain.png') }}"
            alt=""
            class="hidden h-auto w-full object-contain sm:block sm:max-w-[80%]"
        />
    </div>

    <div class="hp-container relative z-10 grid grid-cols-1 items-start gap-x-12 lg:grid-cols-[1fr_4fr]">
        <div class="mb-4 flex items-center justify-center sm:justify-start">
            <x-he4rt::section-title size="lg">Missão Social</x-he4rt::section-title>
        </div>

        <div class="flex flex-col gap-8">
            <div>
                <x-he4rt::headline class="mx-0">
                    <x-slot:title>Contribua com nossa ação social</x-slot>
                    <x-slot:description>
                        Acreditamos que a tecnologia tem o poder de transformar, e isso começa pela comunidade. O valor
                        da sua inscrição será integralmente revertido para a compra de cestas básicas para uma
                        instituição no Alto do Tietê, que acolhe crianças em lar temporário.
                    </x-slot>
                    <x-slot:actions>
                        <x-he4rt::button>Button</x-he4rt::button>
                    </x-slot>
                </x-he4rt::headline>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <x-he4rt::card>
                    <x-slot:icon class="font-family-secondary text-4xl">01</x-slot>
                    <x-slot:title>Primeiro Passo</x-slot>
                    <x-slot:description>
                        Você faz um PIX de R$60,00 (valor simbólico que cobre uma cesta básica)
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon class="font-family-secondary text-4xl">02</x-slot>
                    <x-slot:title>Segundo Passo</x-slot>
                    <x-slot:description>
                        Você garante sua vaga na transmissão ao vivo e recebe a Camiseta Exclusiva de Lançamento
                        (entregue em sua casa)
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon class="font-family-secondary text-4xl">03</x-slot>
                    <x-slot:title>Terceiro Passo</x-slot>
                    <x-slot:description>
                        Ao seguir nossas redes e compartilhar o post oficial do evento, você fortalece a Comunidade 3
                        Pts e amplia o impacto dessa ação.
                    </x-slot>
                </x-he4rt::card>
                <x-he4rt::card>
                    <x-slot:icon class="font-family-secondary text-4xl">04</x-slot>
                    <x-slot:title>Quarto Passo</x-slot>
                    <x-slot:description>
                        Você se torna um agente de transformação, ajudando a garantir o suporte e o cuidado dessas
                        crianças.
                    </x-slot>
                </x-he4rt::card>
            </div>
        </div>
    </div>
</section>
