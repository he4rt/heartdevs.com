<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Section name</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Fique por dentro da programação do evento</x-slot>
                <x-slot:description>
                    Conteúdos e palestras sobre as mais modernas tecnologias, desde o back-end, até o front-end.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div class="mx-auto mt-24 flex max-w-5xl flex-col gap-4">
            <x-he4rt::schedule-card status="upcoming" />
            <x-he4rt::schedule-card status="in_progress" />
            <x-he4rt::schedule-card />
        </div>
    </div>
</section>
