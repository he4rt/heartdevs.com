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

        <div class="grid w-full grid-cols-1 items-center justify-center gap-8 sm:grid-cols-3 lg:gap-12">
            <x-portal::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-portal::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-portal::card>

            <x-portal::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-portal::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-portal::card>

            <x-portal::card>
                <x-slot:description>
                    "A comunidade Coração Dev mudou completamente minha trajetória profissional. Através dos projetos e
                    mentorias, consegui meu primeiro emprego como desenvolvedor e hoje faço parte de uma empresa
                    incrível."
                </x-slot>
                <x-slot:footer>
                    <x-portal::partials.author
                        size="lg"
                        src="https://avatars.githubusercontent.com/u/103362"
                        name="Daniel Reis"
                        title="DevRel"
                    />
                </x-slot>
            </x-portal::card>
        </div>
    </div>
</section>
