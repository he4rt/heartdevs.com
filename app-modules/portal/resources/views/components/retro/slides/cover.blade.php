@props(['sources', 'since', 'until'])
@php
    $fmt = fn ($d): string => $d instanceof \Carbon\CarbonInterface
        ? $d->timezone(config('app.display_timezone'))->format('d/m/Y')
        : (string) $d;
@endphp
<section class="slide" data-label="Abertura">
    <div class="slide-inner" style="text-align: center; max-width: 900px">
        <div data-anim>
            <img class="cover-heart" src="{{ asset('images/retro-heart.png') }}" width="86" height="86" alt="He4rt" />
        </div>
        <div class="kicker" data-anim style="margin-top: 20px">
            RETROSPECTIVA · {{ $fmt($since) }} — {{ $fmt($until) }}
        </div>
        <h1 class="hero" data-anim>Quem fez a He4rt <em>bater</em></h1>
        <svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
            <path d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560" />
        </svg>
        <p class="lead" data-anim style="margin: 8px auto 0">
            Participação da comunidade <b>He4rt</b> em cada frente — <b>gente, código, conversa e presença</b>.
        </p>
        <div data-anim style="display: flex; flex-direction: column; gap: 18px; margin-top: 26px">
            @foreach ($sources as $source)
                <div>
                    <div
                        class="mono"
                        style="font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--brand-soft); margin-bottom: 9px"
                    >{{ $source->label }}</div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center">
                        @foreach ($source->headline->metrics as $metric)
                            <span class="bdg neu" style="font-size: 1rem; padding: 7px 14px">
                                <b style="color: var(--text)">{{ number_format($metric->value, 0, ',', '.') }}</b>
                                {{ $metric->label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="hint" data-anim>navegue com <kbd>←</kbd> <kbd>→</kbd></div>
    </div>
</section>
