@props(['since', 'until', 'hideBots' => true])
@php
    $toDate = fn ($value): string => $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : (string) $value;
@endphp
<div x-data="{ open: false }" @retro-open-filters.window="open = true" @keydown.escape.window="open = false">
    <div class="scrim" :class="open ? 'open' : ''" @click="open = false"></div>
    <aside class="panel" :class="open ? 'open' : ''">
        <div class="panel-head">
            <h3>Recorte</h3>
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
                    <input type="date" wire:model.live="since" value="{{ $toDate($since) }}" />
                    <input type="date" wire:model.live="until" value="{{ $toDate($until) }}" />
                </div>
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
                        <div class="s">Remove contas automáticas dos números e rankings.</div>
                    </div>
                    <div class="toggle {{ $hideBots ? 'on' : '' }}" wire:click="$toggle('hideBots')"></div>
                </div>
            </div>
        </div>
    </aside>
</div>
