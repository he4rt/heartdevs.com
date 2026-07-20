@php ($noData = count($sources) === 0)
{{-- kind -> partial por convenção: "discord.voice_board" => portal::retro.slides.discord.voice-board --}}
@php ($slidePartial = fn (string $kind): string => 'portal::retro.slides.'.str_replace('_', '-', $kind))
<x-portal::retro.deck :stateKey="$stateKey" :bare="$noData">
    @unless ($noData)
        <x-slot:filters>
            <x-portal::retro.controls :since="$since" :until="$until" :hideBots="$hideBots" />
        </x-slot:filters>
    @endunless

    @if ($noData)
        <x-portal::retro.slides.empty />
    @else
        <x-portal::retro.slides.cover :sources="$sources" :since="$since" :until="$until" />

        @foreach ($sources as $source)
            @foreach ($source->slides as $slide)
                @include($slidePartial($slide->kind()), $slide->toArray())
            @endforeach
        @endforeach

        <x-portal::retro.slides.closing :sources="$sources" :since="$since" :until="$until" />
    @endif
</x-portal::retro.deck>
