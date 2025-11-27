<section class="hp-section relative items-start" id="hero">
    <div class="absolute inset-0 -left-[20%] z-0 h-full w-[150%]">
        <img src="{{ asset('images/3pontos/logo-chain.png') }}" alt="" class="h-full w-full object-cover" />

        <div class="bg-elevation-surface/90 absolute inset-0"></div>
        <div class="from-elevation-surface/80 absolute inset-0 bg-gradient-to-t to-transparent"></div>
    </div>

    <div
        class="pointer-events-none absolute top-1/2 right-0 z-5 hidden w-[600px] translate-x-1/3 -translate-y-1/2 xl:block"
    >
        <img
            src="{{ asset('images/3pontos/logo-rounded.svg') }}"
            alt=""
            class="spin-slow h-auto w-full object-contain"
        />
    </div>

    <div class="hp-container relative z-10">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div
                x-data="{
                    copied: false,
                    link: '{{ url('/event/3pontos') }}',
                    copyToClipboard() {
                        navigator.clipboard.writeText(this.link)
                        this.copied = true
                        setTimeout(() => (this.copied = false), 2000)
                    },
                }"
            >
                <x-he4rt::headline>
                    <x-slot:badge>
                        <x-he4rt::section-title>Área do Participante</x-he4rt::section-title>
                    </x-slot>
                    <x-slot:title>Parabéns! Sua vaga está garantida!</x-slot>
                    <x-slot:description>Acesse o link abaixo para conferir o seu ticket.</x-slot>
                    <x-slot:actions>
                        <x-he4rt::button>Compartilhar no...</x-he4rt::button>

                        <div x-show="!copied">
                            <x-he4rt::button variant="outline" icon="far-copy" @click="copyToClipboard()">
                                Copiar
                            </x-he4rt::button>
                        </div>

                        <div x-show="copied" x-cloak>
                            <x-he4rt::button variant="outline" icon="fas-check">Copiado</x-he4rt::button>
                        </div>
                    </x-slot>
                </x-he4rt::headline>
            </div>

            <div
                x-data="{ visible: false }"
                x-intersect.once="visible = true"
                class="p-2 sm:p-4 lg:p-8 xl:ml-auto xl:min-w-2xl"
            >
                <x-he4rt::animate-block type="blur">
                    <x-he4rt::ticket
                        user-img="auth()->user()->"
                        username="danielhe4rt"
                        github-username="NexTurHe4rt"
                        ticketNumber="42"
                        githubLink="https://github.com/john-doe"
                        githubText="nexturhe4rt"
                        twitchLink="https://twitch.tv/john"
                        twitchText="danielhe4rt"
                        eventDate="10/12, 20 horas"
                        eventSubtitle="Ao vivo no Twitch"
                    />
                </x-he4rt::animate-block>
            </div>
        </div>
    </div>
</section>
