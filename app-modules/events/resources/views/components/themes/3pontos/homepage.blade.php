<x-he4rt::layouts.page class="relative" full-height="true">
    <x-events::themes.3pontos.components.sections.hero />
    <x-events::themes.3pontos.components.sections.social-action />
    <x-events::themes.3pontos.components.sections.community />
    <x-events::themes.3pontos.components.sections.meet-up />
    <x-events::themes.3pontos.components.sections.speakers />
    <x-events::themes.3pontos.components.sections.schedule />
    <x-events::themes.3pontos.components.sections.watch-live />
    <x-events::themes.3pontos.components.sections.partners />
    <x-events::themes.3pontos.components.sections.about />
    <x-events::themes.3pontos.components.sections.info />
    <div class="absolute -bottom-10 z-0 translate-x-[60%] translate-y-1/3 lg:-translate-x-[60%]">
        <img src="{{ asset('images/3pontos/logo-creation.png') }}" class="max-h-[700px]" alt="" />
    </div>

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
