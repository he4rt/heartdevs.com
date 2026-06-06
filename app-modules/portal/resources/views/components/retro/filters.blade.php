@props ([
    'repoOptions' => [],
    'repos' => [],
    'types' => [],
    'hideBots' => true,
    'byRepo' => true,
    'showHighlights' => true
])
@php
    $typeOptions = [
        'pr' => ['PRs', '--t-pr'],
        'review' => ['Reviews', '--t-review'],
        'issue' => ['Issues', '--t-issue'],
        'comment' => ['Comentários', '--t-comment'],
        'commit' => ['Commits', '--t-commit'],
    ];
@endphp
<div x-data="{ open: false }" @retro-open-filters.window="open = true" @keydown.escape.window="open = false">
    <div class="scrim" :class="open ? 'open' : ''" @click="open = false"></div>
    <aside class="panel" :class="open ? 'open' : ''">
        <div class="panel-head">
            <h3>Filtros</h3>
            <button type="button" class="x" @click="open = false">&times;</button>
        </div>
        <div class="panel-body">
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4.5" width="18" height="17" rx="2" />
                        <line x1="16" y1="2.5" x2="16" y2="6.5" />
                        <line x1="8" y1="2.5" x2="8" y2="6.5" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    Período
                </div>
                <div class="chips">
                    <span class="chip" wire:click="setPreset('semana')">Última semana</span>
                    <span class="chip" wire:click="setPreset('mes')">Último mês</span>
                    <span class="chip" wire:click="setPreset('tudo')">Tudo</span>
                </div>
                <div class="dates" style="margin-top: 10px">
                    <input type="date" wire:model.live="since" />
                    <input type="date" wire:model.live="until" />
                </div>
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                    Repositórios
                </div>
                <div class="chips">
                    @forelse ($repoOptions as $full => $name)
                        <span
                            class="chip {{ empty($repos) || in_array($full, $repos, true) ? 'on' : '' }}"
                            wire:click="toggleRepo('{{ $full }}')"
                            >{{ $name }}</span
                        >
                    @empty
                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 0.68rem; color: var(--faint)"
                            >nenhum repo com dados ainda</span
                        >
                    @endforelse
                </div>
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <circle cx="6" cy="6" r="3" />
                        <circle cx="18" cy="18" r="3" />
                        <path d="M13 6h3a2 2 0 0 1 2 2v7" />
                        <line x1="6" y1="9" x2="6" y2="21" />
                    </svg>
                    Tipos de contribuição
                </div>
                <div class="chips">
                    @foreach ($typeOptions as $key => [$label, $color])
                        <span
                            class="chip {{ empty($types) || in_array($key, $types, true) ? 'on' : '' }}"
                            style="--ck: var({{ $color }})"
                            wire:click="toggleType('{{ $key }}')"
                            ><span class="d"></span>{{ $label }}</span
                        >
                    @endforeach
                </div>
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 6 7 17l-4-4" />
                        <path d="m22 10-7.5 7.5L13 16" style="opacity: 0.5" />
                    </svg>
                    Desfecho de PR
                </div>
                <select class="fsel" wire:model.live="outcome">
                    <option value="">Todos</option>
                    <option value="merged">Merged</option>
                    <option value="open">Abertos</option>
                    <option value="closed">Fechados sem merge</option>
                </select>
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Pessoa
                </div>
                <input type="text" class="fsel" placeholder="login do GitHub" wire:model.live.debounce.400ms="person" />
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <line x1="4" y1="7" x2="20" y2="7" />
                        <line x1="6" y1="12" x2="18" y2="12" />
                        <line x1="9" y1="17" x2="15" y2="17" />
                    </svg>
                    Ordenar ranking
                </div>
                <select class="fsel" wire:model.live="sort">
                    <option value="total">Mais interações</option>
                    <option value="prs">Mais PRs</option>
                    <option value="lines">Mais linhas mudadas</option>
                </select>
            </div>
            <div class="fgroup">
                <div class="flabel">
                    <svg viewBox="0 0 24 24">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    Exibição
                </div>
                <div class="frow">
                    <div>
                        <div class="t">Ocultar bots</div>
                        <div class="s">Remove contas automáticas (ex.: dependabot) do ranking.</div>
                    </div>
                    <div class="toggle {{ $hideBots ? 'on' : '' }}" wire:click="$toggle('hideBots')"></div>
                </div>
                <div class="frow">
                    <div>
                        <div class="t">Slides por repositório</div>
                        <div class="s">Um slide para cada repositório, listando seus PRs.</div>
                    </div>
                    <div class="toggle {{ $byRepo ? 'on' : '' }}" wire:click="$toggle('byRepo')"></div>
                </div>
                <div class="frow">
                    <div>
                        <div class="t">Slide de maiores PRs</div>
                        <div class="s">Um slide com os PRs de maior impacto no período (por linhas mudadas).</div>
                    </div>
                    <div class="toggle {{ $showHighlights ? 'on' : '' }}" wire:click="$toggle('showHighlights')"></div>
                </div>
            </div>
        </div>
    </aside>
</div>
