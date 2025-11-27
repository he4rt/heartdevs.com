@props([
    'event',
])
<section class="hp-section" id="schedule">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Lineup</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Fique por dentro da programação do evento</x-slot>
                <x-slot:description>
                    Conteúdos e palestras sobre as mais modernas tecnologias, desde o back-end, até o front-end.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div class="mt-24 flex w-full max-w-5xl flex-col gap-4">
            @forelse ($event->talks()->orderBy('starts_at', 'asc')->get() as $talk)
                <x-he4rt::animate-block observe type="fade-right">
                    <x-he4rt::schedule-card starts-at="{{$talk->start}}" title="{{$talk->title}}" />
                </x-he4rt::animate-block>
            @empty
                <p>There is no talk yet.</p>
            @endforelse
        </div>
    </div>
</section>
