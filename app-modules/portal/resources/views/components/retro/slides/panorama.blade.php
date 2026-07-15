@props (['meta'])
<section class="slide" data-label="Panorama">
    <div class="slide-inner">
        <span class="sec-tag" data-anim>O panorama</span>
        <h2 class="sec" data-anim>O que a comunidade entregou</h2>
        <p class="sec-sub" data-anim>
            <b style="color: var(--text)">{{ $meta['people'] }} pessoas</b> somaram
            <b style="color: var(--text)">{{ $meta['total'] }} interações</b>
            em {{ $meta['repos'] }} @choice('repositório|repositórios', $meta['repos']).
        </p>
        <div class="stats" data-anim style="margin-top: 24px">
            <div class="stat">
                <div class="v accent">{{ $meta['people'] }}</div>
                <div class="l">Pessoas</div>
            </div>
            <div class="stat">
                <div class="v">{{ $meta['prs'] }}</div>
                <div class="l">Pull Requests</div>
            </div>
            <div class="stat">
                <div class="v">{{ $meta['reviews'] }}</div>
                <div class="l">Reviews</div>
            </div>
            <div class="stat">
                <div class="v">{{ $meta['issues'] }}</div>
                <div class="l">Issues</div>
            </div>
            <div class="stat">
                <div class="v">{{ $meta['comments'] + $meta['review_comments'] }}</div>
                <div class="l">Comentários</div>
            </div>
            <div class="stat">
                <div class="v accent">{{ $meta['commits'] }}</div>
                <div class="l">Commits</div>
            </div>
        </div>
        <div data-anim style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 22px">
            <span class="bdg add" style="font-size: 1rem; padding: 7px 14px"
                >+{{ number_format($meta['additions'], 0, ',', '.') }} linhas</span
            >
            <span class="bdg del" style="font-size: 1rem; padding: 7px 14px"
                >−{{ number_format($meta['deletions'], 0, ',', '.') }} linhas</span
            >
            <span class="bdg neu" style="font-size: 1rem; padding: 7px 14px"
                >{{ number_format($meta['changed_files'], 0, ',', '.') }} arquivos</span
            >
            <span class="bdg neu" style="font-size: 1rem; padding: 7px 14px"
                >{{ $meta['prs_merged'] }} merged · {{ $meta['prs_unmerged'] }} fechados</span
            >
        </div>
    </div>
</section>
