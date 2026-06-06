@props (['meta', 'people', 'period'])
<section class="slide" data-label="Obrigado">
    <div class="slide-inner" style="text-align: center; max-width: 940px">
        <h2 class="sec" data-anim>Obrigado a quem fez<br />o coração bater 💜</h2>
        <p class="sec-sub" data-anim style="margin: 0 auto">
            {{ $meta['people'] }} pessoas, {{ $meta['total'] }} interações, {{ number_format($meta['additions'], 0, ',', '.') }} linhas.
            Toda contribuição manteve a He4rt viva.
        </p>
        <div data-anim style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 30px">
            @foreach ($people as $p)
                <a href="{{ $p['url'] }}" target="_blank" rel="noopener" title="{{ '@' . $p['login'] }}"
                    ><img
                        class="mini"
                        src="{{ $p['avatar'] }}"
                        onerror="this.onerror=null;this.src='https://github.com/{{ $p['login'] }}.png'"
                        width="50"
                        height="50"
                        alt="{{ $p['login'] }}"
                        style="width: 50px; height: 50px"
                /></a>
            @endforeach
        </div>
        <p
            class="hint"
            data-anim
            style="margin-top: 30px"
        >gerado a partir da GitHub API · {{ $period['since'] }} — {{ $period['until'] }}</p>
    </div>
</section>
