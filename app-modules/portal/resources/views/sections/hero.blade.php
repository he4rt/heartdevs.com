<section class="hp-section relative min-h-[calc(100svh-6rem)]!" id="community">
    <div
        class="pointer-events-none absolute -z-1 flex h-full w-full items-center justify-center overflow-hidden p-8 opacity-40 sm:p-16"
        aria-hidden="true"
    >
        <x-portal::animated-logo class="w-full max-w-5xl" />
    </div>
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="flex flex-col gap-4">
                <x-he4rt::headline size="2xl" :keywords="['potencial']">
                    <x-slot:badge>
                        <x-he4rt::badge>
                            <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5" />
                            Comunidade Open Source
                        </x-he4rt::badge>
                    </x-slot:badge>

                    <x-slot:title>
                        Desenvolva seu potencial na comunidade
                    </x-slot:title>

                    <x-slot:description>
                        Uma comunidade de desenvolvedores dedicada a ajudar iniciantes a se tornarem profissionais
                        através de projetos, mentorias e networking.
                    </x-slot:description>
                    <x-slot:actions>
                        <x-he4rt::button href="https://discord.gg/he4rt" icon="heroicon-s-chevron-right">
                            Começar agora
                        </x-he4rt::button>

                        <x-he4rt::button
                            href="https://github.com/he4rt"
                            icon="heroicon-s-chevron-right"
                            variant="outline"
                        >
                            Explorar projetos
                        </x-he4rt::button>
                    </x-slot:actions>
                </x-he4rt::headline>
                <div class="flex flex-col items-center gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <x-he4rt::avatar-stack :images="$this->avatars" :limit="count($this->avatars)" size="sm" />
                    <span class="text-text-medium text-center text-sm sm:text-left">
                        Mais de {{ number_format($this->usersCount, thousands_separator: '.') }} desenvolvedores já
                        fazem parte
                    </span>
                </div>
            </div>
            <div class="flex min-w-0 flex-col items-center justify-center gap-6">
                <x-portal::terminal :stats="$this->terminalStats" />

                <x-he4rt::card
                    href="/docs"
                    density="compact"
                    class="h-auto w-full max-w-md shadow-lg lg:max-w-lg"
                    aria-label="Abrir documentação da comunidade"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-current/15 bg-current/8">
                            <x-filament::icon icon="heroicon-o-light-bulb" class="h-6 w-6" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-text-high text-base font-semibold">
                                Novo por aqui?
                            </p>
                            <p class="text-text-medium text-sm">
                                Confira nossa documentação e descubra como participar.
                            </p>
                        </div>

                        <x-filament::icon icon="heroicon-o-chevron-right" class="text-primary h-5 w-5 shrink-0" />
                    </div>
                </x-he4rt::card>
            </div>
        </div>
    </div>
</section>
