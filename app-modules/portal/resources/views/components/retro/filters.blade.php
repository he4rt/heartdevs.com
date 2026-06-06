@props ([
    'repoOptions' => [],
    'repos' => [],
    'types' => [],
    'hideBots' => true,
    'byRepo' => true,
    'showHighlights' => true
])
<div x-data="{ open: false }" @retro-open-filters.window="open = true" @keydown.escape.window="open = false">
    <div class="scrim" :class="open ? 'open' : ''" @click="open = false"></div>
    <aside class="panel" :class="open ? 'open' : ''">
        <div class="panel-head">
            <h3>Filtros</h3>
            <button type="button" class="x" @click="open = false">&times;</button>
        </div>
        <div class="panel-body">
            <div class="fgroup">
                <div class="flabel">Período</div>
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
                <div class="flabel">Repositórios</div>
                <div class="chips">
                    @forelse ($repoOptions as $full => $name)
                        <span
                            class="chip {{ empty($repos) || in_array($full, $repos, true) ? 'on' : '' }}"
                            wire:click="toggleRepo('{{ $full }}')"
                            >{{ $name }}</span
                        >
                    @empty
                        <span class="flabel" style="margin: 0">nenhum repo com dados ainda</span>
                    @endforelse
                </div>
            </div>
            <div class="fgroup">
                <div class="flabel">Tipos</div>
                <div class="chips">
                    @foreach ([
                            'pr' => 'PRs',
                            'review' => 'Reviews',
                            'issue' => 'Issues',
                            'comment' => 'Comentários',
                            'commit' => 'Commits'
                        ]
                        as $key => $label)
                        <span
                            class="chip {{ empty($types) || in_array($key, $types, true) ? 'on' : '' }}"
                            wire:click="toggleType('{{ $key }}')"
                            >{{ $label }}</span
                        >
                    @endforeach
                </div>
            </div>
            <div class="fgroup">
                <div class="flabel">Desfecho de PR</div>
                <select class="fsel" wire:model.live="outcome">
                    <option value="">Todos</option>
                    <option value="merged">Merged</option>
                    <option value="open">Abertos</option>
                    <option value="closed">Fechados sem merge</option>
                </select>
            </div>
            <div class="fgroup">
                <div class="flabel">Pessoa</div>
                <input type="text" class="fsel" placeholder="login do GitHub" wire:model.live.debounce.400ms="person" />
            </div>
            <div class="fgroup">
                <div class="flabel">Ordenar ranking</div>
                <select class="fsel" wire:model.live="sort">
                    <option value="total">Mais interações</option>
                    <option value="prs">Mais PRs</option>
                    <option value="lines">Mais linhas mudadas</option>
                </select>
            </div>
            <div class="fgroup">
                <div class="frow">
                    <div class="t">Ocultar bots</div>
                    <div class="toggle {{ $hideBots ? 'on' : '' }}" wire:click="$toggle('hideBots')"></div>
                </div>
                <div class="frow">
                    <div class="t">Slides por repositório</div>
                    <div class="toggle {{ $byRepo ? 'on' : '' }}" wire:click="$toggle('byRepo')"></div>
                </div>
                <div class="frow">
                    <div class="t">Slide de destaques</div>
                    <div class="toggle {{ $showHighlights ? 'on' : '' }}" wire:click="$toggle('showHighlights')"></div>
                </div>
            </div>
        </div>
    </aside>
</div>
