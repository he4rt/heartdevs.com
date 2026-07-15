@props (['repo', 'index' => 1])
<section class="slide" data-label="{{ $repo['name'] }}">
    <div class="slide-inner">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap" data-anim>
            <span
                class="mono"
                style="font-size: 0.74rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--brand-soft)"
                >Repositório {{ $index }}</span
            >
            <span class="avstack" style="margin-left: auto">
                @foreach (array_slice($repo['people'], 0, 6) as $p)
                    <img
                        class="mini"
                        src="{{ $p['avatar'] }}"
                        onerror="this.onerror=null;this.src='https://github.com/{{ $p['login'] }}.png'"
                        width="36"
                        height="36"
                        alt="{{ $p['login'] }}"
                        style="width: 36px; height: 36px"
                    />
                @endforeach
            </span>
        </div>
        <div style="display: flex; gap: 16px; align-items: center; margin-top: 14px" data-anim>
            <div class="repo-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--brand-soft)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                </svg>
            </div>
            <div>
                <h2 class="repo-name">{{ $repo['name'] }}</h2>
                <div class="handle">{{ $repo['full_name'] }}</div>
            </div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 9px; margin: 18px 0" data-anim>
            <span class="bdg neu">{{ $repo['metrics']['prs'] }} PRs</span>
            <span class="bdg neu">{{ number_format($repo['metrics']['changed_files'], 0, ',', '.') }} arquivos</span>
            @if ($repo['metrics']['additions'] > 0)
                <span class="bdg add">+{{ number_format($repo['metrics']['additions'], 0, ',', '.') }}</span>
            @endif
            @if ($repo['metrics']['deletions'] > 0)
                <span class="bdg del">−{{ number_format($repo['metrics']['deletions'], 0, ',', '.') }}</span>
            @endif
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px">
            @foreach ($repo['prs'] as $pr)
                <div data-anim><x-portal::retro.pr-row :pr="$pr" /></div>
            @endforeach
        </div>
    </div>
</section>
