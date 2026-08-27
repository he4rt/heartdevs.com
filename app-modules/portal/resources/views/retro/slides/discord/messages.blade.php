<section class="slide" data-label="Conversas">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O papo</span>
        <h2 class="sec" data-anim>O que rolou no chat</h2>
        <p class="sec-sub" data-anim>
            <b style="color: var(--text)">{{ number_format($total, 0, ',', '.') }} mensagens</b> trocadas no período.
        </p>
        <div data-anim style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 18px">
            <span class="bdg neu" style="font-size: 1rem; padding: 7px 14px"
                >{{ number_format($with_reactions, 0, ',', '.') }} com reação</span
            >
            <span class="bdg neu" style="font-size: 1rem; padding: 7px 14px"
                >{{ number_format($pinned, 0, ',', '.') }} fixadas</span
            >
        </div>
        @if (count($chatters))
            <div style="margin-top: 22px; display: flex; flex-direction: column; gap: 10px">
                @foreach ($chatters as $i => $chatter)
                    <div class="card" data-anim style="display: flex; align-items: center; gap: 12px">
                        <span class="mono" style="color: var(--brand-soft); min-width: 28px">#{{ $i + 1 }}</span>
                        <span style="color: var(--text); font-weight: 600">{{ $chatter['name'] }}</span>
                        <span class="mono" style="margin-left: auto; color: var(--faint)"
                            >{{ number_format($chatter['messages'], 0, ',', '.') }} msgs</span
                        >
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
