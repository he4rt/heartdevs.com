@props(['sources', 'since', 'until'])
@php
    $fmt = fn ($d): string => $d instanceof \Carbon\CarbonInterface
        ? $d->timezone(config('app.display_timezone'))->format('d/m/Y')
        : (string) $d;
    $labels = collect($sources)->map(fn ($source): string => $source->label)->implode(', ');
@endphp
<section class="slide" data-label="Obrigado">
    <div class="slide-inner" style="text-align: center; max-width: 940px">
        <h2 class="sec" data-anim>Obrigado a quem fez<br />o coração bater 💜</h2>
        <p class="sec-sub" data-anim style="margin: 0 auto">
            Cada PR, cada mensagem, cada call e cada reação manteve a He4rt viva.
        </p>
        <div data-anim style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 30px">
            @foreach ($sources as $source)
                <span class="bdg neu" style="font-size: 1rem; padding: 8px 16px">{{ $source->label }}</span>
            @endforeach
        </div>
        <p class="hint" data-anim style="margin-top: 30px">
            gerado a partir de {{ $labels }} · {{ $fmt($since) }} — {{ $fmt($until) }}
        </p>
    </div>
</section>
