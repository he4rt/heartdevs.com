<x-he4rt::layouts.page class="relative" full-height="true">
    <x-events::themes.3pontos.components.sections.event-ticket />
    <x-events::themes.3pontos.components.sections.event-progress />
    <x-events::themes.3pontos.components.sections.partners />
    <x-events::themes.3pontos.components.sections.info />
    <x-he4rt::partials.footer
        logoPath="images/3pontos/logo.svg"
        logoSize="sm"
        description="Somos o ecossistema que une solução e conhecimento em um único lugar. Aceleramos sua empresa. Fortalecemos sua carreira."
        company="3 Pontos"
        :columns="[
            'Navegação' => [
                'Home' => '#',
                'Missão social' => '#social-action',
                'Comunidade' => '#community',
                'Propósito' => '#meet-up',
                'Palestrantes' => '#speakers',
                'Lineup' => '#lineup',
                'Ao vivo' => '#watch-live',
                'Parceiros' => '#partners',
                'Saiba mais' => '#about',
            ]
        ]"
    />
</x-he4rt::layouts.page>
