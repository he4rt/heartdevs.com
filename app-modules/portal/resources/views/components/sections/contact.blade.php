<section class="hp-section">
    <div class="hp-container">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="border-border flex flex-col gap-8 rounded-lg border p-8">
                <x-portal::headline size="md">
                    <x-slot:title>Entre em contato conosco</x-slot>
                    <x-slot:description>
                        Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa comunidade.
                    </x-slot>
                </x-portal::headline>

                <hr />

                <x-portal::input label="Nome completo" />
                <x-portal::input label="Email" />
                <x-portal::textarea label="Mensagem" />

                <x-portal::button>Enviar mensagem</x-portal::button>
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-8 p-8">
                    <x-portal::headline align="center" size="md">
                        <x-slot:title>Venha fazer parte do nosso discord</x-slot>
                        <x-slot:description>
                            Histórias reais de desenvolvedores que transformaram suas carreiras através da nossa
                            comunidade.
                        </x-slot>
                        <x-slot:actions>
                            <x-portal::button>Entrar no Discord</x-portal::button>
                        </x-slot>
                    </x-portal::headline>

                    <div class="border-border flex flex-col gap-4 rounded-lg border p-8">
                        <x-portal::headline class="mx-0" size="sm">
                            <x-slot:title>Redes sociais</x-slot>
                            <x-slot:description>Lorem ipsum dolor sit amet, consectetur</x-slot>
                            <x-slot:actions>
                                <x-filament::icon
                                    icon="fab-discord"
                                    class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                />
                                <x-filament::icon
                                    icon="fab-linkedin"
                                    class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                />
                                <x-filament::icon
                                    icon="fab-x-twitter"
                                    class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                />
                                <x-filament::icon
                                    icon="fab-instagram"
                                    class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                />
                                <x-filament::icon
                                    icon="fab-github"
                                    class="h-6 w-6 transition-all duration-500 hover:scale-105"
                                />
                            </x-slot>
                        </x-portal::headline>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
