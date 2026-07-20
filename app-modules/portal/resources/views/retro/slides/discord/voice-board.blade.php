<section class="slide" data-label="Voz">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>As calls</span>
        <h2 class="sec" data-anim>Quem viveu no voice</h2>
        <p class="sec-sub" data-anim>
            <b style="color: var(--text)">{{ number_format($participants, 0, ',', '.') }} pessoas</b> passaram pelas calls,
            somando <b style="color: var(--text)">{{ number_format($xp, 0, ',', '.') }} XP</b> de presença.
        </p>
        @if (count($channels))
            <div class="stats" data-anim style="margin-top: 24px">
                @foreach ($channels as $channel)
                    <div class="stat">
                        <div class="v accent">{{ number_format($channel['xp'], 0, ',', '.') }}</div>
                        <div class="l">{{ $channel['name'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
