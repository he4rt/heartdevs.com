@props (['meta', 'period'])
<section class="slide" data-label="Abertura">
    <div class="slide-inner" style="text-align: center; max-width: 900px">
        <div data-anim>
            <img class="cover-heart" src="{{ asset('images/retro-heart.png') }}" width="86" height="86" alt="He4rt" />
        </div>
        <div class="kicker" data-anim style="margin-top: 20px">
            RETROSPECTIVA · {{ $period['since'] }} — {{ $period['until'] }}
        </div>
        <h1 class="hero" data-anim>Quem fez a He4rt <em>bater</em></h1>
        <svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
            <path
                class="trace"
                d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
            />
            <path
                class="pulse"
                pathLength="100"
                d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560"
            />
        </svg>
        <p
            class="lead"
            data-anim
            style="margin: 8px auto 0"
        >Participação da comunidade <b>He4rt</b> nos repositórios públicos, em <b>gente, código e contexto</b>. <span class="num">{{ $meta['people'] }} pessoas</span>, <span class="num">{{ $meta['total'] }} interações</span>, <span class="num">{{ number_format($meta['additions'], 0, ',', '.') }} linhas</span>.</p>
        <div class="hint" data-anim>navegue com <kbd>←</kbd> <kbd>→</kbd></div>
    </div>
</section>
