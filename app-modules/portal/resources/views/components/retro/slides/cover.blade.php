@props (['meta', 'period'])
<section class="slide" data-label="Abertura">
    <div class="slide-inner" style="text-align: center; max-width: 900px">
        <svg viewBox="0 0 24 24" width="78" height="78" data-anim style="
                margin: 0 auto;
                fill: var(--brand);
                filter: drop-shadow(0 0 22px rgba(120, 43, 241, 0.7));
            ">
            <path d="M12 21s-8-4.6-8-11.1A4.9 4.9 0 0 1 12 7a4.9 4.9 0 0 1 8 2.9C20 16.4 12 21 12 21z" />
        </svg>
        <div class="kicker" data-anim style="margin-top: 20px">
            RETROSPECTIVA · {{ $period['since'] }} — {{ $period['until'] }}
        </div>
        <h1 class="hero" data-anim>Quem fez a He4rt <em>bater</em></h1>
        <svg class="ecg" viewBox="0 0 560 60" preserveAspectRatio="none" data-anim aria-hidden="true">
            <path d="M0 34 H210 l10 0 l8 -7 l11 14 l13 -34 l15 50 l11 -22 l9 0 H340 l9 0 l8 -6 l9 12 l12 -28 l13 40 l10 -16 l8 0 H560" />
        </svg>
        <p class="lead" data-anim style="
                margin: 8px auto 0;
            ">Participação da comunidade <b>He4rt</b> nos repositórios públicos, em <b>gente, código e contexto</b>. <span class="num">{{ $meta['people'] }} pessoas</span>, <span class="num">{{ $meta['total'] }} interações</span>, <span class="num">{{ number_format($meta['additions'], 0, ',', '.') }} linhas</span>.</p>
        <div class="hint" data-anim>navegue com <kbd>←</kbd> <kbd>→</kbd></div>
    </div>
</section>
