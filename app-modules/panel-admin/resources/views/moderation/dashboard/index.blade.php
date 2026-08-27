<div wire:poll.60s>
    {{-- Controls: Period filter + Tabs --}}
    <div class="mb-6 space-y-3">
        {{-- Period filter: select on mobile, segmented on desktop --}}
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <span
                class="shrink-0 text-xs font-semibold tracking-widest text-zinc-400 uppercase"
                >{{ __('panel-admin::moderation.dashboard.filter_period') }}</span
            >
            <div class="sm:hidden">
                <flux:select wire:model.live="period" size="sm">
                    @foreach (__('panel-admin::moderation.dashboard.periods') as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="hidden sm:block">
                <flux:radio.group wire:model.live="period" variant="segmented" size="sm">
                    @foreach (__('panel-admin::moderation.dashboard.periods') as $value => $label)
                        <flux:radio value="{{ $value }}" label="{{ $label }}" />
                    @endforeach
                </flux:radio.group>
            </div>
        </div>

        {{-- Tabs: select on mobile, segmented on desktop --}}
        <div class="sm:hidden">
            <flux:select wire:model.live="activeTab">
                <flux:select.option value="overview">Visao Geral</flux:select.option>
                <flux:select.option value="classification">Classificacao & IA</flux:select.option>
                <flux:select.option value="team">Equipe</flux:select.option>
                <flux:select.option value="appeals">Appeals & SLA</flux:select.option>
            </flux:select>
        </div>
        <div class="hidden sm:block">
            <flux:radio.group wire:model.live="activeTab" variant="segmented">
                <flux:radio value="overview" icon="chart-bar" label="Visao Geral" />
                <flux:radio value="classification" icon="cpu-chip" label="IA" />
                <flux:radio value="team" icon="user-group" label="Equipe" />
                <flux:radio value="appeals" icon="scale" label="Appeals" />
            </flux:radio.group>
        </div>
    </div>

    {{-- ==================== TAB: OVERVIEW ==================== --}}
    @if ($activeTab === 'overview')
        <div class="space-y-4">
            <x-he4rt::dashboard.stat-row columns="5">
                <x-he4rt::dashboard.stat-card
                    :label="__('panel-admin::moderation.dashboard.stats.pending')"
                    :value="$this->pendingCount"
                    :description="__('panel-admin::moderation.dashboard.stats.pending_desc')"
                    color="amber"
                    icon="clock"
                />
                <x-he4rt::dashboard.stat-card
                    :label="__('panel-admin::moderation.dashboard.stats.resolved')"
                    :value="$this->resolvedCount"
                    :description="__('panel-admin::moderation.dashboard.stats.resolved_desc')"
                    color="green"
                    icon="check-circle"
                />
                <x-he4rt::dashboard.stat-card
                    :label="__('panel-admin::moderation.dashboard.stats.avg_time')"
                    :value="$this->avgResolutionMinutes"
                    :description="__('panel-admin::moderation.dashboard.stats.avg_time_desc')"
                    color="blue"
                    icon="clock"
                />
                <x-he4rt::dashboard.stat-card
                    :label="__('panel-admin::moderation.dashboard.stats.appeal_rate')"
                    :value="$this->appealRate . '%'"
                    :description="__('panel-admin::moderation.dashboard.stats.appeal_rate_desc')"
                    color="violet"
                    icon="scale"
                />
                <x-he4rt::dashboard.stat-card
                    label="Saude"
                    :value="$this->healthScore"
                    description="score composto"
                    :color="$this->healthScore >= 70 ? 'green' : ($this->healthScore >= 40 ? 'amber' : 'red')"
                />
            </x-he4rt::dashboard.stat-row>

            <x-he4rt::dashboard.grid columns="1">
                <x-he4rt::dashboard.panel
                    title="Atividade por hora e dia"
                    subtitle="ultimos 30 dias"
                    :badge="$this->casesByStatus->sum() . ' total'"
                >
                    <x-he4rt::dashboard.heatmap
                        variant="week"
                        :data="$this->activityHeatmap"
                        icon="shield-check"
                        :highlightNow="true"
                        insightDetail="Concentre staff de plantao nos horarios de pico"
                    />
                </x-he4rt::dashboard.panel>
            </x-he4rt::dashboard.grid>

            <x-he4rt::dashboard.grid>
                <x-he4rt::dashboard.panel
                    :title="__('panel-admin::moderation.dashboard.cases_by_status')"
                    :badge="$this->casesByStatus->sum() . ' total'"
                >
                    <x-he4rt::dashboard.progress-list
                        :items="
                            collect(\He4rt\Moderation\Enums\CaseStatus::cases())
        ->map(
            fn($s) => [
                'label' => $s->getLabel(),
                'count' => $this->casesByStatus->get($s->value, 0),
                'color' => \Filament\Support\Colors\Color::convertToHex($s->getColor()[500]),
            ],
        )
        ->filter(fn($i) => $i['count'] > 0)
        ->values()
        ->toArray()
                        "
                        :total="$this->casesByStatus->sum()"
                    />
                </x-he4rt::dashboard.panel>

                <x-he4rt::dashboard.panel :title="__('panel-admin::moderation.dashboard.cases_by_platform')">
                    <x-he4rt::dashboard.progress-list
                        :items="
                            collect(\He4rt\Moderation\Enums\Platform::cases())
        ->map(
            fn($p) => [
                'label' => $p->getLabel(),
                'count' => $this->casesByPlatform->get($p->value, 0),
                'color' => \Filament\Support\Colors\Color::convertToHex($p->getColor()[500]),
            ],
        )
        ->filter(fn($i) => $i['count'] > 0)
        ->values()
        ->toArray()
                        "
                        :total="$this->casesByPlatform->sum()"
                    />
                </x-he4rt::dashboard.panel>
            </x-he4rt::dashboard.grid>

            <x-he4rt::dashboard.grid>
                <x-he4rt::dashboard.panel :title="__('panel-admin::moderation.dashboard.top_violations')">
                    @php
                        $violationTotal = $this->violationCounts->sum();
                    @endphp
                    <x-he4rt::dashboard.bar-chart
                        :items="
                            $this->violationCounts
        ->map(
            fn($count, $type) => [
                'label' => \He4rt\Moderation\Enums\ViolationType::from($type)->getLabel(),
                'value' => $violationTotal > 0 ? round(($count / $violationTotal) * 100) : 0,
                'color' => \Filament\Support\Colors\Color::convertToHex(
                    \He4rt\Moderation\Enums\ViolationType::from($type)->getColor()[500],
                ),
            ],
        )
        ->values()
        ->toArray()
                        "
                        :max="100"
                    />
                </x-he4rt::dashboard.panel>

                <x-he4rt::dashboard.panel :title="__('panel-admin::moderation.dashboard.false_positive.heading')">
                    <div class="py-2 text-center">
                        <div
                            class="text-4xl font-black {{ $this->falsePositiveRate > 15 ? 'text-red-500' : 'text-emerald-500' }}"
                        >
                            {{ $this->falsePositiveRate }}%
                        </div>
                        <div
                            class="mt-1 text-sm {{ $this->falsePositiveRate < $this->previousFalsePositiveRate ? 'text-emerald-500' : ($this->falsePositiveRate > $this->previousFalsePositiveRate ? 'text-red-400' : 'text-zinc-400') }}"
                        >
                            @if ($this->falsePositiveRate < $this->previousFalsePositiveRate)
                                {{
                                    __('panel-admin::moderation.dashboard.false_positive.improving', [
                                        'prev' => $this->previousFalsePositiveRate,
                                    ])
                                }}
                            @elseif ($this->falsePositiveRate > $this->previousFalsePositiveRate)
                                {{
                                    __('panel-admin::moderation.dashboard.false_positive.worsening', [
                                        'prev' => $this->previousFalsePositiveRate,
                                    ])
                                }}
                            @else
                                {{
                                    __(
                                        'panel-admin::moderation.dashboard.false_positive.stable',
                                    )
                                }}
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                            <div class="text-xs font-semibold text-zinc-400 uppercase">OpenAI</div>
                            <div
                                class="mt-1 text-lg font-bold {{ $this->fpRateOpenAi > 15 ? 'text-red-400' : 'text-emerald-400' }}"
                            >
                                {{ $this->fpRateOpenAi }}%
                            </div>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                            <div class="text-xs font-semibold text-zinc-400 uppercase">Regras</div>
                            <div
                                class="mt-1 text-lg font-bold {{ $this->fpRateRules > 15 ? 'text-amber-400' : 'text-emerald-400' }}"
                            >
                                {{ $this->fpRateRules }}%
                            </div>
                        </div>
                    </div>
                </x-he4rt::dashboard.panel>
            </x-he4rt::dashboard.grid>

            {{-- Activity: Repeat Offenders --}}
            <x-he4rt::dashboard.grid>
                <x-he4rt::dashboard.panel title="Reincidentes" badge="top 5">
                    @forelse ($this->repeatOffenders as $offender)
                        <div
                            class="flex items-center justify-between border-b border-zinc-100 py-2.5 last:border-0 dark:border-zinc-700/50"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    @class ([
                                        'font-mono text-sm font-bold',
                                        'text-red-500' => $offender->offense_count >= 5,
                                        'text-amber-500' => $offender->offense_count >= 3 && $offender->offense_count < 5,
                                        'text-zinc-400' => $offender->offense_count < 3
                                    ])
                                    >{{ $offender->offense_count }}</span
                                >
                                <span
                                    class="text-sm font-medium dark:text-zinc-200"
                                    >{{ '@' . $offender->username }}</span
                                >
                            </div>
                            <flux:badge
                                size="sm"
                                :color="$offender->offense_count >= 5 ? 'red' : ($offender->offense_count >= 3 ? 'amber' : 'zinc')"
                                >{{ $offender->offense_count }} infracoes
                            </flux:badge>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-400">Nenhum reincidente no periodo</p>
                    @endforelse
                </x-he4rt::dashboard.panel>
            </x-he4rt::dashboard.grid>

            {{-- Recent Actions --}}
            <x-he4rt::dashboard.panel
                :title="__('panel-admin::moderation.dashboard.recent_actions')"
                badge="ultimas 12"
                :compact="true"
            >
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Acao</flux:table.column>
                        <flux:table.column>Moderador</flux:table.column>
                        <flux:table.column>Violacao</flux:table.column>
                        <flux:table.column>Auto</flux:table.column>
                        <flux:table.column align="end">Quando</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentActions as $action)
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:badge
                                        size="sm"
                                        :color="
                                            match ($action->action_type->value) {
        'ban' => 'red',
        'warn' => 'amber',
        'mute' => 'zinc',
        'kick' => 'blue',
        'suspend' => 'orange',
        'content_remove' => 'violet',
        default => 'zinc',
    }
                                        "
                                    >
                                        {{ strtoupper($action->action_type->value) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{
                                    $action->moderator
                                        ? '@' . $action->moderator->username
                                        : 'sistema'
                                }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($action->case?->violation_type)
                                        <flux:badge
                                            size="sm"
                                            color="zinc"
                                            >{{ $action->case->violation_type->getLabel() }}</flux:badge
                                        >
                                    @else
                                        <span class="text-zinc-400">--</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($action->automated)
                                        <flux:icon name="bolt" class="h-4 w-4 text-cyan-400" />
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <span
                                        class="text-sm text-zinc-400"
                                        >{{ $action->created_at->diffForHumans(short: true) }}</span
                                    >
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-he4rt::dashboard.panel>
        </div>
    @endif

    {{-- ==================== TAB: CLASSIFICATION ==================== --}}
    @if ($activeTab === 'classification')
        <div class="space-y-4">
            <x-he4rt::dashboard.stat-row columns="3">
                <x-he4rt::dashboard.stat-card
                    label="Falso Positivo"
                    :value="$this->falsePositiveRate . '%'"
                    :description="$this->falsePositiveRate < $this->previousFalsePositiveRate ? 'melhorando' : 'estavel'"
                    :color="$this->falsePositiveRate > 15 ? 'red' : 'green'"
                    :trend="$this->falsePositiveRate < $this->previousFalsePositiveRate ? 'down' : null"
                />
                <x-he4rt::dashboard.stat-card
                    label="Automacao"
                    :value="$this->automationRate . '%'"
                    description="casos auto-resolvidos"
                    color="cyan"
                />
                <x-he4rt::dashboard.stat-card
                    label="Total de Acoes"
                    :value="$this->autoVsManualCounts['total']"
                    :description="$this->autoVsManualCounts['auto'] . ' auto / ' . $this->autoVsManualCounts['manual'] . ' manual'"
                    color="violet"
                />
            </x-he4rt::dashboard.stat-row>

            <x-he4rt::dashboard.grid>
                <x-he4rt::dashboard.panel :title="__('panel-admin::moderation.dashboard.false_positive.heading')">
                    <div class="py-4 text-center">
                        <div
                            class="text-5xl font-black {{ $this->falsePositiveRate > 15 ? 'text-red-500' : 'text-emerald-500' }}"
                        >
                            {{ $this->falsePositiveRate }}%
                        </div>
                        <div
                            class="mt-2 text-sm {{ $this->falsePositiveRate < $this->previousFalsePositiveRate ? 'text-emerald-500' : ($this->falsePositiveRate > $this->previousFalsePositiveRate ? 'text-red-400' : 'text-zinc-400') }}"
                        >
                            @if ($this->falsePositiveRate < $this->previousFalsePositiveRate)
                                {{
                                    __('panel-admin::moderation.dashboard.false_positive.improving', [
                                        'prev' => $this->previousFalsePositiveRate,
                                    ])
                                }}
                            @elseif ($this->falsePositiveRate > $this->previousFalsePositiveRate)
                                {{
                                    __('panel-admin::moderation.dashboard.false_positive.worsening', [
                                        'prev' => $this->previousFalsePositiveRate,
                                    ])
                                }}
                            @else
                                {{
                                    __(
                                        'panel-admin::moderation.dashboard.false_positive.stable',
                                    )
                                }}
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                            <div class="text-xs font-semibold text-zinc-400 uppercase">OpenAI</div>
                            <div
                                class="mt-1 text-xl font-bold {{ $this->fpRateOpenAi > 15 ? 'text-red-400' : 'text-emerald-400' }}"
                            >
                                {{ $this->fpRateOpenAi }}%
                            </div>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900">
                            <div class="text-xs font-semibold text-zinc-400 uppercase">Regras</div>
                            <div
                                class="mt-1 text-xl font-bold {{ $this->fpRateRules > 15 ? 'text-amber-400' : 'text-emerald-400' }}"
                            >
                                {{ $this->fpRateRules }}%
                            </div>
                        </div>
                    </div>
                </x-he4rt::dashboard.panel>

                <x-he4rt::dashboard.panel title="Auto vs Manual">
                    <x-he4rt::dashboard.doughnut-chart
                        :items="
                            [
       ['label' => 'Manual', 'count' => $this->autoVsManualCounts['manual'], 'color' => '#8b5cf6'],
       ['label' => 'Automatico', 'count' => $this->autoVsManualCounts['auto'], 'color' => '#22d3ee'],
    ]
                        "
                        :total="$this->autoVsManualCounts['total']"
                    />
                </x-he4rt::dashboard.panel>
            </x-he4rt::dashboard.grid>
        </div>
    @endif

    {{-- ==================== TAB: TEAM ==================== --}}
    @if ($activeTab === 'team')
        <div class="space-y-4">
            <x-he4rt::dashboard.stat-row columns="4">
                <x-he4rt::dashboard.stat-card
                    label="Moderadores Ativos"
                    :value="$this->moderatorStats->count()"
                    description="neste periodo"
                    color="violet"
                />
                <x-he4rt::dashboard.stat-card
                    label="Media Geral"
                    :value="($this->moderatorStats->avg('avg_minutes') ? round($this->moderatorStats->avg('avg_minutes')) : 0) . 'min'"
                    description="tempo de resposta"
                    color="green"
                />
                <x-he4rt::dashboard.stat-card
                    label="Overturn Geral"
                    :value="$this->overallOverturnRate . '%'"
                    :description="$this->overallOverturnRate < 15 ? 'saudavel' : 'atencao'"
                    :color="$this->overallOverturnRate < 15 ? 'amber' : 'red'"
                />
                <x-he4rt::dashboard.stat-card
                    label="Casos / Mod"
                    :value="$this->moderatorStats->count() > 0 ? round($this->moderatorStats->avg('total_cases')) : 0"
                    description="media por moderador"
                    color="blue"
                />
            </x-he4rt::dashboard.stat-row>

            <x-he4rt::dashboard.panel
                :title="__('panel-admin::moderation.dashboard.moderator_performance.heading')"
                :compact="true"
                :footerText="
                    __('panel-admin::moderation.dashboard.moderator_performance.overall_overturn', [
        'rate' => $this->overallOverturnRate,
    ])
                "
            >
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Moderador</flux:table.column>
                        <flux:table.column>{{
                            __(
                                'panel-admin::moderation.dashboard.moderator_performance.cases',
                            )
                        }}</flux:table.column>
                        <flux:table.column>{{
                            __(
                                'panel-admin::moderation.dashboard.moderator_performance.avg_time',
                            )
                        }}</flux:table.column>
                        <flux:table.column>{{
                            __(
                                'panel-admin::moderation.dashboard.moderator_performance.overturn',
                            )
                        }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->moderatorStats as $mod)
                            <flux:table.row>
                                <flux:table.cell variant="strong">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-gradient-to-br from-violet-500 to-fuchsia-500 text-xs font-bold text-white"
                                        >
                                            {{ strtoupper(substr($mod->username, 0, 2)) }}
                                        </div>
                                        <span>{{ '@' . $mod->username }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $mod->total_cases }}</span></flux:table.cell
                                >
                                <flux:table.cell>
                                    <span
                                        class="font-mono text-sm {{ round($mod->avg_minutes) <= 15 ? 'text-emerald-500' : (round($mod->avg_minutes) <= 30 ? 'text-amber-500' : 'text-red-500') }}"
                                        >{{ round($mod->avg_minutes) }}min</span
                                    ></flux:table.cell
                                >
                                <flux:table.cell>
                                    <span
                                        class="font-mono text-sm {{ $mod->overturn_rate <= 5 ? 'text-emerald-500' : ($mod->overturn_rate <= 10 ? 'text-amber-500' : 'text-red-500') }}"
                                        >{{ $mod->overturn_rate }}%</span
                                    ></flux:table.cell
                                >
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-he4rt::dashboard.panel>
        </div>
    @endif

    {{-- ==================== TAB: APPEALS ==================== --}}
    @if ($activeTab === 'appeals')
        <div class="space-y-4">
            <x-he4rt::dashboard.stat-row columns="5">
                <x-he4rt::dashboard.stat-card
                    label="Abertos"
                    :value="$this->openAppeals->count()"
                    description="aguardando revisao"
                    color="amber"
                />
                <x-he4rt::dashboard.stat-card
                    label="Resolvidos"
                    :value="$this->resolvedAppealsCount"
                    description="este mes"
                    color="green"
                />
                <x-he4rt::dashboard.stat-card
                    label="Revertidos"
                    :value="$this->overturnedAppealsCount"
                    description="decisoes mudadas"
                    color="red"
                />
                <x-he4rt::dashboard.stat-card
                    label="SLA Compliance"
                    :value="$this->slaComplianceRate . '%'"
                    description="dentro do prazo"
                    color="cyan"
                />
                <x-he4rt::dashboard.stat-card
                    label="Resolvidos SLA"
                    :value="$this->resolvedAppealsCount"
                    description="dentro do prazo este mes"
                    color="blue"
                />
            </x-he4rt::dashboard.stat-row>

            <x-he4rt::dashboard.panel
                :title="__('panel-admin::moderation.dashboard.appeal_sla.heading')"
                :badge="$this->openAppeals->count() . ' abertos'"
                :compact="true"
                :footerText="
                    __('panel-admin::moderation.dashboard.appeal_sla.compliance', [
        'count' => $this->resolvedAppealsCount,
        'rate' => $this->slaComplianceRate,
    ])
                "
            >
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column></flux:table.column>
                        <flux:table.column>Appeal</flux:table.column>
                        <flux:table.column>Apelante</flux:table.column>
                        <flux:table.column>Revisor</flux:table.column>
                        <flux:table.column align="end">SLA</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($this->openAppeals as $appeal)
                            @php
                                $isFuture = $appeal->sla_deadline->isFuture();
                                $hours = (int) abs(now()->diffInHours($appeal->sla_deadline));
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>
                                    <span
                                        @class ([
                                            'inline-block h-2 w-2 rounded-full',
                                            'bg-emerald-500 shadow-[0_0_6px_rgba(52,211,153,0.5)]' => $isFuture,
                                            'bg-red-500 shadow-[0_0_6px_rgba(248,113,113,0.5)]' => !$isFuture
                                        ])
                                    ></span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-sm"
                                        >#{{ substr($appeal->id, 0, 8) }}</span
                                    ></flux:table.cell
                                >
                                <flux:table.cell>{{ '@' . ($appeal->appellant?->username ?? '--') }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400">{{
                                    $appeal->reviewer
                                        ? '@' . $appeal->reviewer->username
                                        : '--'
                                }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <span
                                        @class ([ 'font-mono text-sm font-semibold', 'text-emerald-500' => $isFuture, 'text-red-500' => !$isFuture ])
                                    >
                                        {{
                                            $isFuture
                                                ? __('panel-admin::moderation.dashboard.appeal_sla.remaining', ['hours' => $hours])
                                                : __('panel-admin::moderation.dashboard.appeal_sla.overdue', ['hours' => $hours])
                                        }}
                                    </span>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400">
                                    Nenhum appeal aberto
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </x-he4rt::dashboard.panel>
        </div>
    @endif
</div>
