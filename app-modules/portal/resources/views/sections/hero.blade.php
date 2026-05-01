<section class="hp-section relative" id="community">
    <div class="absolute -z-1 flex h-full w-full p-8 sm:p-16">
        <img src="{{ asset('images/landingLogo.svg') }}" alt="Logo" class="h-full w-full" />
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
            <div class="flex min-w-0 flex-col items-center justify-center">
                <x-portal::terminal :stats="$this->terminalStats" />
            </div>
        </div>
    </div>
</section>
