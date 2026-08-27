@assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endassets

<x-filament-panels::page>
    <div
        x-data="{
            chart: null,
            timeline: @js($this->timeline),

            init() {
                this.$nextTick(() => this.renderChart());
            },

            renderChart() {
                const canvas = this.$refs.timelineChart;
                if (!canvas) return;

                if (this.chart) { this.chart.destroy(); this.chart = null; }

                const ctx = canvas.getContext('2d');
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(113,113,122,0.12)' : 'rgba(0,0,0,0.06)';
                const tickColor = isDark ? '#71717a' : '#a1a1aa';

                const grad1 = ctx.createLinearGradient(0, 0, 0, 260);
                grad1.addColorStop(0, 'rgba(139,92,246,0.25)');
                grad1.addColorStop(1, 'rgba(139,92,246,0)');

                const grad2 = ctx.createLinearGradient(0, 0, 0, 260);
                grad2.addColorStop(0, 'rgba(52,211,153,0.2)');
                grad2.addColorStop(1, 'rgba(52,211,153,0)');

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.timeline.map(d => d.day),
                        datasets: [
                            {
                                label: 'Mensagens',
                                data: this.timeline.map(d => d.msgs),
                                borderColor: '#8b5cf6',
                                backgroundColor: grad1,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointBackgroundColor: '#8b5cf6',
                                pointBorderColor: isDark ? '#18181b' : '#fff',
                                pointBorderWidth: 2,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Voice (horas)',
                                data: this.timeline.map(d => d.voiceHours),
                                borderColor: '#34d399',
                                backgroundColor: grad2,
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35,
                                pointRadius: 2,
                                pointBackgroundColor: '#34d399',
                                pointBorderColor: isDark ? '#18181b' : '#fff',
                                pointBorderWidth: 2,
                                yAxisID: 'y1',
                            },
                            {
                                label: 'Usuários',
                                data: this.timeline.map(d => d.users),
                                borderColor: '#fbbf24',
                                borderWidth: 1.5,
                                borderDash: [5, 4],
                                fill: false,
                                tension: 0.35,
                                pointRadius: 0,
                                yAxisID: 'y',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top', align: 'end',
                                labels: { color: tickColor, font: { size: 11 }, boxWidth: 14, boxHeight: 2, padding: 16 },
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#18181b' : '#fff',
                                borderColor: isDark ? 'rgba(139,92,246,0.2)' : 'rgba(0,0,0,0.1)',
                                borderWidth: 1,
                                titleColor: isDark ? '#e4e4e7' : '#18181b',
                                bodyColor: isDark ? '#a1a1aa' : '#71717a',
                                padding: 12, cornerRadius: 8,
                            },
                        },
                        scales: {
                            x: { grid: { color: gridColor }, ticks: { color: tickColor, font: { size: 10 } } },
                            y: { position: 'left', grid: { color: gridColor }, ticks: { color: tickColor, font: { size: 10 } } },
                            y1: { position: 'right', grid: { display: false }, ticks: { color: '#34d399', font: { size: 10 }, callback: v => v + 'h' } },
                        },
                    },
                });
            },
        }"
    >
        {{-- Header + Range selector --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500">Atividade da comunidade no Discord · horário BRT</p>
            </div>
            <select
                wire:model.live="rangeDays"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200"
            >
                <option value="1">Hoje</option>
                <option value="7">7 dias</option>
                <option value="14">14 dias</option>
                <option value="30">30 dias</option>
                <option value="60">60 dias</option>
                <option value="90">90 dias</option>
            </select>
        </div>

        {{-- Period breakdown — PROTOTYPE: 4 variations --}}
        @if (!empty($periodBreakdown['blocks']))
            @php
                $blocks = $periodBreakdown['blocks'];
                $prevBlock = $blocks[0] ?? null;
                $currBlock = $blocks[1] ?? $blocks[0];
                $msgDiff =
                    $prevBlock && $prevBlock['msgs'] > 0 && count($blocks) > 1
                        ? round((($currBlock['msgs'] - $prevBlock['msgs']) / $prevBlock['msgs']) * 100, 1)
                        : null;
                $voiceDiff =
                    $prevBlock && $prevBlock['voice'] > 0 && count($blocks) > 1
                        ? round((($currBlock['voice'] - $prevBlock['voice']) / $prevBlock['voice']) * 100, 1)
                        : null;
                $usersDiff =
                    $prevBlock && $prevBlock['users'] > 0 && count($blocks) > 1
                        ? round((($currBlock['users'] - $prevBlock['users']) / $prevBlock['users']) * 100, 1)
                        : null;

                $barItems = array_map(
                    fn($b) => [
                        'label' => $b['label'],
                        'value' => $b['msgs'] + (int) $b['voice'],
                        'color' => '#8b5cf6',
                        'suffix' => '',
                        'segments' => [
                            ['label' => 'Mensagens', 'value' => $b['msgs'], 'color' => '#8b5cf6'],
                            ['label' => 'Voice', 'value' => (int) $b['voice'], 'color' => '#34d399'],
                        ],
                    ],
                    $blocks,
                );
                $barLegend = [['label' => 'Mensagens', 'color' => '#8b5cf6'], ['label' => 'Voice', 'color' => '#34d399']];
            @endphp
            <div
                class="mb-6 rounded-xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10"
                x-data="{ pbView: 'summary' }"
            >
                <div
                    class="flex items-center justify-between border-b border-zinc-200 px-5 py-3 dark:border-zinc-700/80"
                >
                    <h3 class="text-sm font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                        {{ $periodBreakdown['label'] }}
                    </h3>
                    <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @foreach ([
                                'summary' => 'document-text',
                                'table' => 'list-bullet',
                                'cards' => 'squares-2x2',
                                'bars' => 'chart-bar',
                                'donut' => 'chart-pie'
                            ]
                            as $key => $icon)
                            <button
                                @click="pbView = '{{ $key }}'"
                                :class="pbView === '{{ $key }}'
                                    ? 'bg-violet-600 text-white'
                                    : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200'"
                                class="flex items-center justify-center px-2 py-1.5 transition-all first:rounded-l-md last:rounded-r-md"
                            >
                                <flux:icon name="{{ $icon }}" class="h-3.5 w-3.5" variant="mini" />
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="p-5">
                    {{-- V0: Summary (charts + narrative) --}}
                    <div x-show="pbView === 'summary'" x-cloak>
                        @php
                            $totalMsgs = $currBlock['msgs'] + $prevBlock['msgs'];
                            $totalVoice = $currBlock['voice'] + $prevBlock['voice'];
                            $totalUsers = max($currBlock['users'], $prevBlock['users']);
                            $msgsUp = $msgDiff !== null && $msgDiff >= 0;
                            $voiceUp = $voiceDiff !== null && $voiceDiff >= 0;
                            $usersUp = $usersDiff !== null && $usersDiff >= 0;
                            $bestWeekVoice = $currBlock['voice'] >= $prevBlock['voice'] ? $currBlock['label'] : $prevBlock['label'];
                        @endphp

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {{-- Left: line chart --}}
                            <div
                                x-data="{
                                    sChart: null,
                                    tl: @js($this->timeline),
                                    render() {
                                        const c = this.$refs.sCanvas;
                                        if (!c || c.offsetParent === null) return;
                                        if (this.sChart) { this.sChart.destroy(); this.sChart = null; }
                                        const ctx = c.getContext('2d');
                                        const dk = document.documentElement.classList.contains('dark');
                                        const gc = dk ? 'rgba(113,113,122,0.08)' : 'rgba(0,0,0,0.04)';
                                        const tc = dk ? '#52525b' : '#a1a1aa';

                                        const w1 = this.tl.slice(0, 7);
                                        const w2 = this.tl.slice(7, 14);
                                        const days = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];

                                        this.sChart = new Chart(ctx, {
                                            type:'line',
                                            data: {
                                                labels: days,
                                                datasets: [
                                                    { label:'Msgs · Sem 1', data:w1.map(d=>d.msgs), borderColor:'#8b5cf6', borderWidth:2, tension:0.4, pointRadius:3, pointBackgroundColor:'#8b5cf6', pointBorderWidth:0, yAxisID:'y' },
                                                    { label:'Msgs · Sem 2', data:w2.map(d=>d.msgs), borderColor:'#c084fc', borderWidth:2, borderDash:[5,3], tension:0.4, pointRadius:3, pointBackgroundColor:'#c084fc', pointBorderWidth:0, yAxisID:'y' },
                                                    { label:'Voice · Sem 1', data:w1.map(d=>d.voiceHours), borderColor:'#34d399', borderWidth:1.5, tension:0.4, pointRadius:3, pointBackgroundColor:'#34d399', pointBorderWidth:0, yAxisID:'y1' },
                                                    { label:'Voice · Sem 2', data:w2.map(d=>d.voiceHours), borderColor:'#6ee7b7', borderWidth:1.5, borderDash:[5,3], tension:0.4, pointRadius:3, pointBackgroundColor:'#6ee7b7', pointBorderWidth:0, yAxisID:'y1' },
                                                ],
                                            },
                                            options: {
                                                responsive:true, maintainAspectRatio:false,
                                                interaction:{mode:'index',intersect:false},
                                                plugins: {
                                                    legend:{position:'bottom',labels:{color:tc,font:{size:10},boxWidth:14,boxHeight:2,padding:10}},
                                                    tooltip:{backgroundColor:dk?'#18181b':'#fff',borderColor:dk?'rgba(139,92,246,0.2)':'rgba(0,0,0,0.1)',borderWidth:1,titleColor:dk?'#e4e4e7':'#18181b',bodyColor:dk?'#a1a1aa':'#71717a',padding:10,cornerRadius:6,titleFont:{size:11},bodyFont:{size:10}},
                                                },
                                                scales: {
                                                    x:{grid:{color:gc},ticks:{color:tc,font:{size:9},maxRotation:0}},
                                                    y:{position:'left',grid:{color:gc},ticks:{color:tc,font:{size:9}}},
                                                    y1:{position:'right',grid:{display:false},ticks:{color:'#34d399',font:{size:9},callback:v=>v+'h'}},
                                                },
                                            },
                                        });
                                    },
                                }"
                                x-intersect.once="render()"
                            >
                                <div class="mb-2 text-xs font-medium tracking-wider text-zinc-400 uppercase">
                                    Atividade no período
                                </div>
                                <div style="height: 220px">
                                    <canvas x-ref="sCanvas"></canvas>
                                </div>
                            </div>

                            {{-- Right: narrative --}}
                            <div class="space-y-5">
                                {{-- Mensagens --}}
                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $msgsUp ? 'bg-emerald-500/10' : 'bg-red-500/10' }}"
                                    >
                                        <flux:icon
                                            :name="$msgsUp ? 'arrow-trending-up' : 'arrow-trending-down'"
                                            class="h-4 w-4 {{ $msgsUp ? 'text-emerald-500' : 'text-red-500' }}"
                                            variant="mini"
                                        />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium dark:text-white">Mensagens {{ $msgsUp ? 'cresceram' : 'caíram' }}
                                        <span class="font-mono font-bold {{ $msgsUp ? 'text-emerald-500' : 'text-red-500' }}">{{ abs($msgDiff ?? 0) }}%</span>
                                        na semana atual</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">De <strong class="font-mono dark:text-zinc-300">{{ number_format($prevBlock['msgs']) }}</strong> ({{ $prevBlock['label'] }}) para <strong class="font-mono dark:text-zinc-300">{{ number_format($currBlock['msgs']) }}</strong> ({{ $currBlock['label'] }}). Total: <strong class="font-mono dark:text-zinc-300">{{ number_format($totalMsgs) }}</strong>.</p>
                                    </div>
                                </div>

                                {{-- Voice --}}
                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $voiceUp ? 'bg-emerald-500/10' : 'bg-red-500/10' }}"
                                    >
                                        <flux:icon
                                            name="microphone"
                                            class="h-4 w-4 {{ $voiceUp ? 'text-emerald-500' : 'text-red-500' }}"
                                            variant="mini"
                                        />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium dark:text-white">
                                            Voice {{ $voiceUp ? 'subiu' : 'caiu' }}
                                            <span
                                                class="font-mono font-bold {{ $voiceUp ? 'text-emerald-500' : 'text-red-500' }}"
                                                >{{ abs($voiceDiff ?? 0) }}%</span
                                            >
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            <strong class="font-mono dark:text-zinc-300"
                                                >{{ $prevBlock['voice'] }}h</strong
                                            >
                                            →
                                            <strong class="font-mono dark:text-zinc-300"
                                                >{{ $currBlock['voice'] }}h</strong
                                            >. Semana mais ativa:
                                            <strong class="dark:text-zinc-300">{{ $bestWeekVoice }}</strong>.
                                        </p>
                                    </div>
                                </div>

                                {{-- Users --}}
                                <div class="flex items-start gap-3">
                                    <div
                                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $usersUp ? 'bg-emerald-500/10' : 'bg-amber-500/10' }}"
                                    >
                                        <flux:icon
                                            name="users"
                                            class="h-4 w-4 {{ $usersUp ? 'text-emerald-500' : 'text-amber-500' }}"
                                            variant="mini"
                                        />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium dark:text-white">
                                            Pico de
                                            <span class="font-mono font-bold text-amber-500">{{ $totalUsers }}</span>
                                            usuários únicos
                                            @if ($usersDiff !== null)
                                                <span class="text-xs text-zinc-400"
                                                    >({{ $usersUp ? '+' : '' }}{{ $usersDiff }}%)</span
                                                >
                                            @endif
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $prevBlock['label'] }}:
                                            <strong
                                                class="font-mono dark:text-zinc-300"
                                                >{{ $prevBlock['users'] }}</strong
                                            >
                                            → {{ $currBlock['label'] }}:
                                            <strong
                                                class="font-mono dark:text-zinc-300"
                                                >{{ $currBlock['users'] }}</strong
                                            >
                                        </p>
                                    </div>
                                </div>

                                {{-- Recommendation --}}
                                <div
                                    class="rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 dark:border-violet-800/50 dark:bg-violet-900/20"
                                >
                                    <div class="flex items-center gap-2">
                                        <flux:icon name="light-bulb" class="h-4 w-4 text-violet-500" variant="mini" />
                                        <span class="text-xs font-semibold text-violet-700 dark:text-violet-300"
                                            >Recomendação</span
                                        >
                                    </div>
                                    <p class="mt-1.5 text-xs text-violet-600 dark:text-violet-400">
                                        @if ($msgsUp && $voiceUp)
                                            A comunidade está em crescimento. A semana
                                            <strong>{{ $currBlock['label'] }}</strong>
                                            teve mais atividade em texto e voice — bom momento para agendar eventos e
                                            engajar novos membros.
                                        @elseif ($msgsUp)
                                            Mensagens cresceram mas voice caiu. Considere promover mais eventos com
                                            voice chat para equilibrar a participação.
                                        @elseif ($voiceUp)
                                            Voice cresceu mas mensagens caíram. A comunidade pode estar migrando pra
                                            interações ao vivo — aproveite pra criar mais conteúdo em formato de call.
                                        @else
                                            Atividade geral caiu. Considere ações de reengajamento: eventos temáticos,
                                            challenges, ou conteúdo novo para reativar membros.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- V1: Table --}}
                    <div x-show="pbView === 'table'" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                        <th
                                            class="pb-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                                        >
                                            Período
                                        </th>
                                        <th
                                            class="pb-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                                        >
                                            Mensagens
                                        </th>
                                        <th
                                            class="pb-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                                        >
                                            Voice
                                        </th>
                                        <th
                                            class="pb-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                                        >
                                            Usuários
                                        </th>
                                        <th
                                            class="pb-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                                        >
                                            Variação
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($blocks as $i => $block)
                                        @php
                                            $prev = $blocks[$i - 1] ?? null;
                                            $diff = $prev && $prev['msgs'] > 0 ? round((($block['msgs'] - $prev['msgs']) / $prev['msgs']) * 100, 1) : null;
                                        @endphp
                                        <tr class="border-b border-zinc-50 last:border-0 dark:border-zinc-800">
                                            <td class="py-3 font-mono text-xs font-medium dark:text-zinc-300">
                                                {{ $block['label'] }}
                                            </td>
                                            <td class="py-3 text-right font-mono text-xs font-semibold dark:text-white">
                                                {{ number_format($block['msgs']) }}
                                            </td>
                                            <td
                                                class="py-3 text-right font-mono text-xs text-zinc-500 dark:text-zinc-400"
                                            >
                                                {{ $block['voice'] }}h
                                            </td>
                                            <td
                                                class="py-3 text-right font-mono text-xs text-zinc-500 dark:text-zinc-400"
                                            >
                                                {{ $block['users'] }}
                                            </td>
                                            <td class="py-3 text-right">
                                                @if ($diff !== null)
                                                    <span
                                                        class="font-mono text-xs font-semibold {{ $diff >= 0 ? 'text-emerald-500' : 'text-red-500' }}"
                                                    >
                                                        {{ $diff >= 0 ? '▲' : '▼' }} {{ abs($diff) }}%
                                                    </span>
                                                @else
                                                    <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- V2: Stat cards side by side --}}
                    <div x-show="pbView === 'cards'" x-cloak>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-{{ count($blocks) }}">
                            @foreach ($blocks as $i => $block)
                                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="mb-3 font-mono text-xs font-medium text-zinc-400">
                                        {{ $block['label'] }}
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-baseline justify-between">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Mensagens</span>
                                            <span
                                                class="font-mono text-lg font-bold dark:text-white"
                                                >{{ number_format($block['msgs']) }}</span
                                            >
                                        </div>
                                        <div class="flex items-baseline justify-between">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Voice</span>
                                            <span class="font-mono text-lg font-bold text-emerald-500"
                                                >{{ $block['voice'] }}h</span
                                            >
                                        </div>
                                        <div class="flex items-baseline justify-between">
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Usuários</span>
                                            <span
                                                class="font-mono text-lg font-bold text-amber-500"
                                                >{{ $block['users'] }}</span
                                            >
                                        </div>
                                    </div>
                                    @if ($i > 0 && $msgDiff !== null)
                                        <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-700/50">
                                            <span
                                                class="font-mono text-xs font-semibold {{ $msgDiff >= 0 ? 'text-emerald-500' : 'text-red-500' }}"
                                            >
                                                {{ $msgDiff >= 0 ? '▲' : '▼' }} {{ abs($msgDiff) }}% vs anterior
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- V3: Stacked bar comparison --}}
                    <div x-show="pbView === 'bars'" x-cloak>
                        <x-he4rt::dashboard.bar-chart
                            :items="$barItems"
                            :ranked="false"
                            label-width="w-28"
                            order="none"
                            :stacked="true"
                            :legend="$barLegend"
                        />
                    </div>

                    {{-- V4: Doughnut per block --}}
                    <div x-show="pbView === 'donut'" x-cloak>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-{{ count($blocks) }}">
                            @foreach ($blocks as $block)
                                <div>
                                    <div class="mb-3 font-mono text-xs font-medium text-zinc-400">
                                        {{ $block['label'] }}
                                    </div>
                                    <x-he4rt::dashboard.doughnut-chart
                                        :items="
                                            [
       ['label' => 'Mensagens', 'count' => $block['msgs'], 'color' => '#8b5cf6'],
       ['label' => 'Voice', 'count' => (int) $block['voice'], 'color' => '#34d399'],
       ['label' => 'Usuários', 'count' => $block['users'], 'color' => '#fbbf24'],
    ]
                                        "
                                        :size="110"
                                    />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Activity timeline chart --}}
        <div class="mb-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Atividade Diária</span>
                <span class="font-mono text-xs text-zinc-400"
                    >{{ $this->timeline[0]['day'] ?? '' }} — {{ end($this->timeline)['day'] ?? '' }}</span
                >
            </div>
            <div style="height: 260px">
                <canvas x-ref="timelineChart"></canvas>
            </div>
        </div>

        {{-- Heatmap + Activity by DOW --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 lg:col-span-3 dark:bg-zinc-900 dark:ring-white/10"
            >
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Heatmap de Atividade</span>
                    <span class="font-mono text-xs text-zinc-400">mensagens + voice</span>
                </div>
                <x-he4rt::dashboard.heatmap
                    variant="week"
                    :data="$heatmapData"
                    :showInsight="false"
                    :showLegend="true"
                    accent-color="violet"
                />
            </div>
            <div
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 lg:col-span-2 dark:bg-zinc-900 dark:ring-white/10"
                x-data="{ dowMode: 'all' }"
            >
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Atividade por Dia</span>
                    <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <template
                            x-for="
                                opt in
                                [
                                    { key: 'all', label: 'Tudo' },
                                    { key: 'msgs', label: 'Msgs' },
                                    { key: 'voice', label: 'Voice' },
                                ]
                            "
                        >
                            <button
                                @click="dowMode = opt.key"
                                :class="dowMode === opt.key
                                    ? 'bg-violet-600 text-white'
                                    : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                class="px-2.5 py-1 text-[11px] font-medium transition-all first:rounded-l-md last:rounded-r-md"
                                x-text="opt.label"
                            ></button>
                        </template>
                    </div>
                </div>

                @php
                    $dowBarAll = array_map(
                        fn($d) => [
                            'label' => $d['label'],
                            'value' => $d['total'],
                            'color' => '#8b5cf6',
                            'suffix' => '',
                            'segments' => [
                                ['label' => 'Mensagens', 'value' => $d['msgs'], 'color' => '#8b5cf6'],
                                ['label' => 'Voice', 'value' => $d['voice'], 'color' => '#34d399'],
                            ],
                        ],
                        $activityByDow,
                    );
                    $dowBarMsgs = array_map(
                        fn($d) => ['label' => $d['label'], 'value' => $d['msgs'], 'color' => '#8b5cf6', 'suffix' => ''],
                        $activityByDow,
                    );
                    $dowBarVoice = array_map(
                        fn($d) => ['label' => $d['label'], 'value' => $d['voice'], 'color' => '#34d399', 'suffix' => ''],
                        $activityByDow,
                    );
                    $dowLegend = [['label' => 'Mensagens', 'color' => '#8b5cf6'], ['label' => 'Voice', 'color' => '#34d399']];
                @endphp

                {{-- Mode: All (stacked) --}}
                <div x-show="dowMode === 'all'" x-cloak>
                    <x-he4rt::dashboard.bar-chart
                        :items="$dowBarAll"
                        :ranked="false"
                        label-width="w-10"
                        order="none"
                        :stacked="true"
                        :legend="$dowLegend"
                    />
                </div>

                {{-- Mode: Messages only --}}
                <div x-show="dowMode === 'msgs'" x-cloak>
                    <x-he4rt::dashboard.bar-chart
                        :items="$dowBarMsgs"
                        :ranked="false"
                        label-width="w-10"
                        order="none"
                    />
                </div>

                {{-- Mode: Voice only --}}
                <div x-show="dowMode === 'voice'" x-cloak>
                    <x-he4rt::dashboard.bar-chart
                        :items="$dowBarVoice"
                        :ranked="false"
                        label-width="w-10"
                        order="none"
                    />
                </div>
            </div>
        </div>

        {{-- Top Channels --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Top Canais</span>
                <span class="font-mono text-xs text-zinc-400">{{ count($topChannels) }} canais</span>
            </div>
            <x-he4rt::dashboard.bar-chart :items="$topChannels" label-width="w-28" />
        </div>
    </div>
</x-filament-panels::page>
