<section class="hp-section" id="community">
    <div class="hp-container grid grid-cols-1 items-start gap-x-12 lg:grid-cols-[1fr_5fr]">
        <div
            x-data="{ visible: false }"
            x-intersect.once="visible = true"
            class="mb-4 flex items-center justify-center sm:justify-start"
        >
            <x-he4rt::animate-block duration="700">
                <x-he4rt::section-title size="lg">Comunidade 3 pontos</x-he4rt::section-title>
            </x-he4rt::animate-block>
        </div>

        <div class="flex flex-col gap-8">
            <x-he4rt::headline class="mx-0">
                <x-slot:title>Comunidade 3 Pontos: Seu hub de tecnologia</x-slot>
                <x-slot:description>
                    Onde o networking é real, o aprendizado é contínuo e os desafios são inspiradores. Junte-se a
                    centenas de profissionais de áreas estratégias e capacitados que estão construindo o futuro num
                    ambiente qualificado e propício para que o melhor aconteça.
                </x-slot>
                <x-slot:actions>
                    <x-he4rt::button href="https://discord.gg/dbFkFkaC">Junte-se a nós</x-he4rt::button>
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div
            x-data="{ visible: false }"
            x-intersect.threshold.20="visible = true"
            class="grid grid-cols-1 gap-8 lg:col-span-2 lg:grid-cols-8 lg:gap-4"
        >
            <x-he4rt::animate-block type="blur" class="lg:col-span-5">
                <x-he4rt::card class="relative overflow-hidden">
                    <div class="absolute top-0 left-0 z-0 h-1/2 w-1/2 -translate-x-[50%] -translate-y-[150%]">
                        <img src="{{ asset('images/3pontos/wave.svg') }}" alt="wave-image" />
                    </div>
                    <x-slot:title class="relative z-10">
                        Participe de Desafios que Importam
                    </x-slot>
                    <x-slot:description class="relative z-10">
                        Esqueça exercícios de código genéricos. Na comunidade 3 Pontos, você trabalha em projetos reais
                        propostos por empresas aceleradas. Eventos enriquecedores, desafios técnicos e colaborações que
                        constroem seu portfólio enquanto resolvem problemas do mercado. Sua visão faz a diferença.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block type="blur" class="lg:col-span-3">
                <x-he4rt::card>
                    <x-slot:title>Networking com Líderes e Pares</x-slot>
                    <x-slot:description>
                        Aqui você não é apenas um número. Você é parte de uma comunidade onde líderes de mercado,
                        founders e outros profissionais estão acessíveis. Faça conexões genuínas, encontre mentores,
                        colabore em projetos e expanda sua rede de forma orgânica e significativa.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block type="blur" class="lg:col-span-3">
                <x-he4rt::card class="relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 z-0 translate-x-[50%] translate-y-[60%]">
                        <img src="{{ asset('images/3pontos/donut.svg') }}" alt="donut-image" />
                    </div>
                    <x-slot:title class="relative z-10">
                        Educação Que Acompanha o Mercado
                    </x-slot>
                    <x-slot:description class="relative z-10">
                        O mercado de tecnologia evolui a cada dia. Nós também. Acesso a webinars, workshops e discussões
                        sobre as últimas tendências: inteligência artificial, desenvolvimento web, fintech, cloud
                        computing e muito mais. Aprenda com especialistas e colegas em um ambiente colaborativo.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>

            <x-he4rt::animate-block type="blur" class="lg:col-span-5">
                <x-he4rt::card class="relative overflow-hidden">
                    <div class="absolute top-0 left-0 z-0 h-1/2 w-1/2 -translate-x-[50%] -translate-y-[150%]">
                        <img src="{{ asset('images/3pontos/wave.svg') }}" alt="wave-image" />
                    </div>
                    <x-slot:title class="relative z-10">Vagas e Projetos Exclusivos</x-slot>
                    <x-slot:description class="relative z-10">
                        Empresas parceiras buscam talentos na comunidade 3 Pontos. Vagas de emprego, projetos freelancer
                        e parcerias chegam primeiro para os membros. Sua próxima grande oportunidade pode estar a um
                        clique de distância.
                    </x-slot>
                </x-he4rt::card>
            </x-he4rt::animate-block>
        </div>
    </div>
</section>
