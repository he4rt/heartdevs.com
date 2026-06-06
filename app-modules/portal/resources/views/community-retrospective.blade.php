<x-portal::retro.deck :stateKey="$stateKey">
    <x-slot:filters>
        <x-portal::retro.filters
            :repoOptions="$repoOptions"
            :repos="$repos"
            :types="$types"
            :hideBots="$hideBots"
            :byRepo="$byRepo"
            :showHighlights="$showHighlights"
        />
    </x-slot:filters>

    <x-portal::retro.slides.cover :meta="$data['meta']" :period="$data['period']" />
    <x-portal::retro.slides.panorama :meta="$data['meta']" />

    @if ($byRepo)
        @foreach ($data['repos'] as $i => $repo)
            <x-portal::retro.slides.repo :repo="$repo" :index="$i + 1" />
        @endforeach
    @endif

    @if ($showHighlights && count($data['highlights']))
        <x-portal::retro.slides.highlights :highlights="$data['highlights']" />
    @endif

    @if (count($data['people']))
        <x-portal::retro.slides.core :people="$data['people']" />
        @if (count($data['people']) > 5)
            <x-portal::retro.slides.community :people="$data['people']" />
        @endif
    @endif

    <x-portal::retro.slides.closing :meta="$data['meta']" :people="$data['people']" :period="$data['period']" />
</x-portal::retro.deck>
