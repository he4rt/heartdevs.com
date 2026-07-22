@php ($noData = count($sources) === 0)
{{-- kind -> partial por convenção: "discord.voice_board" => portal::retro.slides.discord.voice-board --}}
@php ($slidePartial = fn (string $kind): string => 'portal::retro.slides.'.str_replace('_', '-', $kind))
<x-portal::retro.deck :stateKey="$stateKey" :bare="$noData">
    @if ($noData)
        <x-portal::retro.slides.empty />
    @else
        <x-portal::retro.slides.cover
            :sources="$sources"
            :since="$since"
            :until="$until"
            :coverTitle="$coverTitle ?? null"
            :coverIntro="$coverIntro ?? null"
        />

        @foreach ($sources as $source)
            @foreach ($source->slides as $slide)
                @include($slidePartial($slide->kind()), $slide->toArray())
            @endforeach
        @endforeach

        <x-portal::retro.slides.closing
            :sources="$sources"
            :since="$since"
            :until="$until"
            :closingText="$closingText ?? null"
        />
    @endif
</x-portal::retro.deck>
