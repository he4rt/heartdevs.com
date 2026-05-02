# Moderation Dashboard Widgets — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the minimal 2-widget moderation dashboard with a full 8-widget analytics dashboard with global time period filtering.

**Architecture:** Switch `ModerationDashboard` from `extends Page` to `extends Dashboard` to gain native `HasFiltersForm` support. All widgets use `InteractsWithPageFilters` + a shared `ResolvesFilterPeriod` trait to scope queries by the selected time period. Chart widgets extend `ChartWidget` (not deprecated subclasses). All data is queried in real-time.

**Tech Stack:** Filament v5 widgets (`StatsOverviewWidget`, `ChartWidget`, `TableWidget`), Chart.js via Filament, Eloquent aggregates, `InteractsWithPageFilters`.

---

### Task 1: Translations — Add dashboard widget keys

**Files:**

- Modify: `app-modules/panel-admin/lang/en/moderation.php:52-54`
- Modify: `app-modules/panel-admin/lang/pt_BR/moderation.php:52-54`

- [ ] **Step 1: Add English translations**

Replace the existing `'dashboard'` key in `app-modules/panel-admin/lang/en/moderation.php`:

```php
    'dashboard' => [
        'heading' => 'Moderation Overview',
        'filter_period' => 'Period',
        'periods' => [
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            'month' => 'This month',
            'year' => 'This year',
        ],
        'stats' => [
            'pending' => 'Pending',
            'pending_desc' => 'cases',
            'resolved' => 'Resolved',
            'resolved_desc' => 'this period',
            'avg_time' => 'Avg. Time',
            'avg_time_desc' => 'min to resolve',
            'appeal_rate' => 'Appeal Rate',
            'appeal_rate_desc' => 'of actions',
        ],
        'cases_by_status' => 'Cases by Status',
        'cases_by_platform' => 'Cases by Platform',
        'top_violations' => 'Top Violation Types',
        'false_positive' => [
            'heading' => 'False Positive Rate',
            'improving' => '↓ improving (was :prev% last period)',
            'worsening' => '↑ worsening (was :prev% last period)',
            'stable' => '→ stable',
            'by_classifier' => 'By classifier:',
            'fp_suffix' => 'FP',
        ],
        'moderator_performance' => [
            'heading' => 'Moderator Performance',
            'cases' => 'Cases',
            'avg_time' => 'Avg. Time',
            'overturn' => 'Overturn',
            'overall_overturn' => 'Overturned appeals (overall): :rate% (healthy: <15%)',
        ],
        'appeal_sla' => [
            'heading' => 'Appeal SLA',
            'remaining' => ':hours h remaining',
            'overdue' => ':hours h overdue',
            'compliance' => ':count appeals resolved within SLA this month (:rate%)',
        ],
        'recent_actions' => 'Recent Actions',
    ],
```

- [ ] **Step 2: Add Portuguese translations**

Replace the existing `'dashboard'` key in `app-modules/panel-admin/lang/pt_BR/moderation.php`:

```php
    'dashboard' => [
        'heading' => 'Visão Geral da Moderação',
        'filter_period' => 'Período',
        'periods' => [
            '7d' => 'Últimos 7 dias',
            '30d' => 'Últimos 30 dias',
            '90d' => 'Últimos 90 dias',
            'month' => 'Este mês',
            'year' => 'Este ano',
        ],
        'stats' => [
            'pending' => 'Pendentes',
            'pending_desc' => 'casos',
            'resolved' => 'Resolvidos',
            'resolved_desc' => 'este período',
            'avg_time' => 'Tempo Médio',
            'avg_time_desc' => 'min p/ resolver',
            'appeal_rate' => 'Taxa de Appeal',
            'appeal_rate_desc' => 'das ações',
        ],
        'cases_by_status' => 'Casos por Status',
        'cases_by_platform' => 'Casos por Plataforma',
        'top_violations' => 'Top Tipos de Violação',
        'false_positive' => [
            'heading' => 'Taxa de Falso Positivo',
            'improving' => '↓ melhorando (era :prev% período anterior)',
            'worsening' => '↑ piorando (era :prev% período anterior)',
            'stable' => '→ estável',
            'by_classifier' => 'Por classificador:',
            'fp_suffix' => 'FP',
        ],
        'moderator_performance' => [
            'heading' => 'Performance dos Moderadores',
            'cases' => 'Casos',
            'avg_time' => 'Tempo Médio',
            'overturn' => 'Overturn',
            'overall_overturn' => 'Appeals revertidos (geral): :rate% (saudável: <15%)',
        ],
        'appeal_sla' => [
            'heading' => 'SLA de Appeals',
            'remaining' => ':hours h restantes',
            'overdue' => ':hours h atrasado',
            'compliance' => ':count appeals resolvidos no SLA este mês (:rate%)',
        ],
        'recent_actions' => 'Ações Recentes',
    ],
```

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-admin/lang/en/moderation.php app-modules/panel-admin/lang/pt_BR/moderation.php
git commit -m "feat(moderation): add dashboard widget translation keys"
```

---

### Task 2: ResolvesFilterPeriod trait

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/Concerns/ResolvesFilterPeriod.php`

- [ ] **Step 1: Create the trait**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets\Concerns;

use Carbon\Carbon;

trait ResolvesFilterPeriod
{
    protected function periodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };
    }

    protected function previousPeriodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(14),
            '90d' => now()->subDays(180),
            'month' => now()->subMonth()->startOfMonth(),
            'year' => now()->subYear()->startOfYear(),
            default => now()->subDays(60),
        };
    }
}
```

- [ ] **Step 2: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-admin/src/Moderation/Widgets/Concerns/ResolvesFilterPeriod.php
git commit -m "feat(moderation): add ResolvesFilterPeriod trait for dashboard widgets"
```

---

### Task 3: ModerationDashboard page — switch to Dashboard with filters

**Files:**

- Modify: `app-modules/panel-admin/src/Moderation/Pages/ModerationDashboard.php`

- [ ] **Step 1: Rewrite ModerationDashboard**

Replace the entire file content:

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Widgets\AppealSlaWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByPlatformChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\FalsePositiveRateWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModerationStatsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModeratorPerformanceWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\TopViolationTypesChartWidget;
use UnitEnum;

class ModerationDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static ?string $cluster = ModerationCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'dashboard';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.dashboard');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_overview');
    }

    public function getTitle(): string
    {
        return __('panel-admin::moderation.dashboard.heading');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('period')
                        ->label(__('panel-admin::moderation.dashboard.filter_period'))
                        ->options(__('panel-admin::moderation.dashboard.periods'))
                        ->default('30d'),
                ])
                ->columns(3),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            ModerationStatsWidget::class,
            CasesByStatusChartWidget::class,
            CasesByPlatformChartWidget::class,
            TopViolationTypesChartWidget::class,
            FalsePositiveRateWidget::class,
            ModeratorPerformanceWidget::class,
            AppealSlaWidget::class,
            RecentActionsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
```

- [ ] **Step 2: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-admin/src/Moderation/Pages/ModerationDashboard.php
git commit -m "feat(moderation): switch dashboard to Filament Dashboard with HasFiltersForm"
```

---

### Task 4: ModerationStatsWidget (① Stats Overview)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/ModerationStatsWidget.php`
- Delete: `app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusWidget.php`

- [ ] **Step 1: Create ModerationStatsWidget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;
use Illuminate\Support\Facades\DB;

class ModerationStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $start = $this->periodStart();

        $pending = ModerationCase::query()->where('status', 'pending')->count();

        $resolved = ModerationCase::query()->where('status', 'resolved')->where('resolved_at', '>=', $start)->count();

        $avgMinutes = (int) ModerationCase::query()
            ->where('status', 'resolved')
            ->where('resolved_at', '>=', $start)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        $actionsCount = ModerationAction::query()->where('created_at', '>=', $start)->count();

        $appealsCount = ModerationAppeal::query()->where('created_at', '>=', $start)->count();

        $appealRate = $actionsCount > 0 ? round(($appealsCount / $actionsCount) * 100) : 0;

        return [
            Stat::make(__('panel-admin::moderation.dashboard.stats.pending'), $pending)
                ->description(__('panel-admin::moderation.dashboard.stats.pending_desc'))
                ->icon(Heroicon::OutlinedClock)
                ->color(Color::Amber),
            Stat::make(__('panel-admin::moderation.dashboard.stats.resolved'), $resolved)
                ->description(__('panel-admin::moderation.dashboard.stats.resolved_desc'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color(Color::Green),
            Stat::make(__('panel-admin::moderation.dashboard.stats.avg_time'), $avgMinutes)
                ->description(__('panel-admin::moderation.dashboard.stats.avg_time_desc'))
                ->icon(Heroicon::OutlinedClock)
                ->color(Color::Blue),
            Stat::make(__('panel-admin::moderation.dashboard.stats.appeal_rate'), $appealRate . '%')
                ->description(__('panel-admin::moderation.dashboard.stats.appeal_rate_desc'))
                ->icon(Heroicon::OutlinedScale)
                ->color(Color::Purple),
        ];
    }
}
```

- [ ] **Step 2: Delete the old CasesByStatusWidget**

```bash
rm app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusWidget.php
```

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app-modules/panel-admin/src/Moderation/Widgets/ModerationStatsWidget.php
git rm app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusWidget.php
git commit -m "feat(moderation): add ModerationStatsWidget, remove old CasesByStatusWidget"
```

---

### Task 5: CasesByStatusChartWidget (② Doughnut)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusChartWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class CasesByStatusChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.cases_by_status');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $start = $this->periodStart();

        $counts = ModerationCase::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $counts->sum();
        $labels = [];
        $data = [];
        $colors = [];

        foreach (CaseStatus::cases() as $status) {
            $count = $counts->get($status->value, 0);
            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            $labels[] = $status->getLabel() . " ({$count}, {$pct}%)";
            $data[] = $count;
            $colors[] = Color::convertToHex($status->getColor()[500]);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusChartWidget.php
git commit -m "feat(moderation): add CasesByStatusChartWidget (doughnut)"
```

---

### Task 6: CasesByPlatformChartWidget (③ Doughnut)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/CasesByPlatformChartWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\Platform;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class CasesByPlatformChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.cases_by_platform');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $start = $this->periodStart();

        $counts = ModerationCase::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('source_platform, count(*) as total')
            ->groupBy('source_platform')
            ->pluck('total', 'source_platform');

        $total = $counts->sum();
        $labels = [];
        $data = [];
        $colors = [];

        foreach (Platform::cases() as $platform) {
            $count = $counts->get($platform->value, 0);

            if ($count === 0) {
                continue;
            }

            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            $labels[] = $platform->getLabel() . " ({$count}, {$pct}%)";
            $data[] = $count;
            $colors[] = Color::convertToHex($platform->getColor()[500]);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/CasesByPlatformChartWidget.php
git commit -m "feat(moderation): add CasesByPlatformChartWidget (doughnut)"
```

---

### Task 7: TopViolationTypesChartWidget (④ Horizontal Bar)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/TopViolationTypesChartWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class TopViolationTypesChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.top_violations');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(
            <<<'JS'
                {
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: { display: false },
                        y: { grid: { display: false } },
                    },
                }
            JS
            ,
        );
    }

    protected function getData(): array
    {
        $start = $this->periodStart();

        $counts = ModerationCase::query()
            ->where('created_at', '>=', $start)
            ->whereNotNull('violation_type')
            ->selectRaw('violation_type, count(*) as total')
            ->groupBy('violation_type')
            ->orderByDesc('total')
            ->pluck('total', 'violation_type');

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($counts as $value => $count) {
            $type = ViolationType::from($value);
            $labels[] = $type->getLabel();
            $data[] = $count;
            $colors[] = Color::convertToHex($type->getColor()[500]);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/TopViolationTypesChartWidget.php
git commit -m "feat(moderation): add TopViolationTypesChartWidget (horizontal bar)"
```

---

### Task 8: FalsePositiveRateWidget (⑤ Stats)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/FalsePositiveRateWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;
use Illuminate\Database\Eloquent\Builder;

class FalsePositiveRateWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.false_positive.heading');
    }

    protected function getStats(): array
    {
        $start = $this->periodStart();
        $prevStart = $this->previousPeriodStart();

        $currentRate = $this->fpRate($start, now());
        $previousRate = $this->fpRate($prevStart, $start);

        if ($currentRate < $previousRate) {
            $description = __('panel-admin::moderation.dashboard.false_positive.improving', ['prev' => $previousRate]);
            $descriptionColor = Color::Green;
            $descriptionIcon = Heroicon::MiniArrowTrendingDown;
        } elseif ($currentRate > $previousRate) {
            $description = __('panel-admin::moderation.dashboard.false_positive.worsening', ['prev' => $previousRate]);
            $descriptionColor = Color::Red;
            $descriptionIcon = Heroicon::MiniArrowTrendingUp;
        } else {
            $description = __('panel-admin::moderation.dashboard.false_positive.stable');
            $descriptionColor = Color::Gray;
            $descriptionIcon = Heroicon::MiniMinus;
        }

        $stats = [
            Stat::make(__('panel-admin::moderation.dashboard.false_positive.heading'), $currentRate . '%')
                ->description($description)
                ->descriptionIcon($descriptionIcon)
                ->descriptionColor($descriptionColor)
                ->color($currentRate > 15 ? Color::Red : Color::Green),
        ];

        foreach ([CaseSource::AutoDetect, CaseSource::RuleMatch] as $source) {
            $rate = $this->fpRateBySource($start, now(), $source);
            $stats[] = Stat::make($source->getLabel(), $rate . '%')->description(
                __('panel-admin::moderation.dashboard.false_positive.fp_suffix'),
            );
        }

        return $stats;
    }

    private function fpRate(mixed $from, mixed $to): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }

    private function fpRateBySource(mixed $from, mixed $to, CaseSource $source): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('source', $source)
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/FalsePositiveRateWidget.php
git commit -m "feat(moderation): add FalsePositiveRateWidget with period comparison"
```

---

### Task 9: ModeratorPerformanceWidget (⑥ Table)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/ModeratorPerformanceWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;
use Illuminate\Database\Eloquent\Builder;

class ModeratorPerformanceWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.moderator_performance.heading');
    }

    public function table(Table $table): Table
    {
        $start = $this->periodStart();

        return $table
            ->query(
                ModerationAction::query()
                    ->where('moderation_actions.created_at', '>=', $start)
                    ->whereNotNull('moderator_id')
                    ->join('moderation_cases', 'moderation_actions.case_id', '=', 'moderation_cases.id')
                    ->selectRaw('moderation_actions.moderator_id')
                    ->selectRaw('count(*) as total_cases')
                    ->selectRaw(
                        'AVG(EXTRACT(EPOCH FROM (moderation_actions.created_at - moderation_cases.created_at)) / 60) as avg_minutes',
                    )
                    ->selectRaw(
                        '(SELECT count(*) FROM moderation_appeals WHERE moderation_appeals.action_id = ANY(ARRAY_AGG(moderation_actions.id)) AND moderation_appeals.status = ?) as overturned_count',
                        ['overturned'],
                    )
                    ->groupBy('moderation_actions.moderator_id')
                    ->orderByDesc('total_cases')
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('moderator.username')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.heading'))
                    ->formatStateUsing(
                        fn(ModerationAction $record): string => '@' . ($record->moderator?->username ?? '—'),
                    ),
                TextColumn::make('total_cases')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.cases'))
                    ->numeric(),
                TextColumn::make('avg_minutes')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.avg_time'))
                    ->formatStateUsing(fn(ModerationAction $record): string => ((int) $record->avg_minutes) . 'min'),
                TextColumn::make('overturned_count')
                    ->label(__('panel-admin::moderation.dashboard.moderator_performance.overturn'))
                    ->formatStateUsing(function (ModerationAction $record): string {
                        $total = $record->total_cases;
                        $overturned = $record->overturned_count;
                        $rate = $total > 0 ? round(($overturned / $total) * 100) : 0;

                        return $rate . '%';
                    }),
            ])
            ->paginated(false);
    }

    public function getTableDescription(): ?string
    {
        $start = $this->periodStart();

        $totalActions = ModerationAction::query()->where('created_at', '>=', $start)->count();

        $overturned = \He4rt\Moderation\Appeals\ModerationAppeal::query()
            ->where('created_at', '>=', $start)
            ->where('status', 'overturned')
            ->count();

        $rate = $totalActions > 0 ? round(($overturned / $totalActions) * 100) : 0;

        return __('panel-admin::moderation.dashboard.moderator_performance.overall_overturn', ['rate' => $rate]);
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/ModeratorPerformanceWidget.php
git commit -m "feat(moderation): add ModeratorPerformanceWidget (table)"
```

---

### Task 10: AppealSlaWidget (⑦ Table)

**Files:**

- Create: `app-modules/panel-admin/src/Moderation/Widgets/AppealSlaWidget.php`

- [ ] **Step 1: Create the widget**

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Appeals\ModerationAppeal;

class AppealSlaWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.appeal_sla.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ModerationAppeal::query()
                    ->with(['appellant', 'reviewer'])
                    ->whereIn('status', ['pending', 'reviewing'])
                    ->orderBy('sla_deadline'),
            )
            ->columns([
                IconColumn::make('sla_status')
                    ->label('')
                    ->state(
                        fn(ModerationAppeal $record): string => $record->sla_deadline->isFuture()
                            ? 'on_track'
                            : 'overdue',
                    )
                    ->icon(
                        fn(string $state): Heroicon => $state === 'on_track'
                            ? Heroicon::MiniCheckCircle
                            : Heroicon::MiniExclamationCircle,
                    )
                    ->color(fn(string $state): string => $state === 'on_track' ? 'success' : 'danger'),
                TextColumn::make('id')
                    ->label('Appeal')
                    ->formatStateUsing(fn(string $state): string => '#' . mb_substr($state, 0, 8)),
                TextColumn::make('appellant.username')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.appellant'))
                    ->formatStateUsing(fn(?string $state): string => $state ? '@' . $state : '—'),
                TextColumn::make('reviewer.username')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.action_moderator'))
                    ->formatStateUsing(fn(?string $state): string => $state ? '@' . $state : '—'),
                TextColumn::make('sla_deadline')
                    ->label('SLA')
                    ->formatStateUsing(function (ModerationAppeal $record): string {
                        $hours = (int) abs(now()->diffInHours($record->sla_deadline));

                        return $record->sla_deadline->isFuture()
                            ? __('panel-admin::moderation.dashboard.appeal_sla.remaining', ['hours' => $hours])
                            : __('panel-admin::moderation.dashboard.appeal_sla.overdue', ['hours' => $hours]);
                    }),
            ])
            ->paginated(false);
    }

    public function getTableDescription(): ?string
    {
        $startOfMonth = now()->startOfMonth();

        $resolvedInSla = ModerationAppeal::query()
            ->where('resolved_at', '>=', $startOfMonth)
            ->whereNotNull('resolved_at')
            ->whereColumn('resolved_at', '<=', 'sla_deadline')
            ->count();

        $totalResolved = ModerationAppeal::query()
            ->where('resolved_at', '>=', $startOfMonth)
            ->whereNotNull('resolved_at')
            ->count();

        $rate = $totalResolved > 0 ? round(($resolvedInSla / $totalResolved) * 100) : 100;

        return __('panel-admin::moderation.dashboard.appeal_sla.compliance', [
            'count' => $resolvedInSla,
            'rate' => $rate,
        ]);
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/AppealSlaWidget.php
git commit -m "feat(moderation): add AppealSlaWidget (table with SLA tracking)"
```

---

### Task 11: Update RecentActionsWidget (⑧ — add period filter)

**Files:**

- Modify: `app-modules/panel-admin/src/Moderation/Widgets/RecentActionsWidget.php`

- [ ] **Step 1: Add filter traits and scope query**

Replace the entire file content:

```php
<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class RecentActionsWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.recent_actions');
    }

    public function table(Table $table): Table
    {
        $start = $this->periodStart();

        return $table
            ->query(
                ModerationAction::query()
                    ->with(['moderator', 'case'])
                    ->where('created_at', '>=', $start)
                    ->latest('created_at')
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('action_type')->badge(),
                TextColumn::make('moderator.username')
                    ->label('Moderator')
                    ->formatStateUsing(fn(?string $state): string => $state ? '@' . $state : '—'),
                TextColumn::make('case.violation_type')->label('Violation')->badge(),
                IconColumn::make('automated')->boolean()->label('Automated'),
                TextColumn::make('created_at')->dateTime()->label('When')->sortable(),
            ])
            ->paginated(false);
    }
}
```

- [ ] **Step 2: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Moderation/Widgets/RecentActionsWidget.php
git commit -m "feat(moderation): add period filter to RecentActionsWidget"
```

---

### Task 12: Dashboard feature tests

**Files:**

- Create: `app-modules/panel-admin/tests/Feature/Moderation/ModerationDashboardTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\PanelAdmin\Moderation\Pages\ModerationDashboard;
use He4rt\PanelAdmin\Moderation\Widgets\AppealSlaWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByPlatformChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\CasesByStatusChartWidget;
use He4rt\PanelAdmin\Moderation\Widgets\FalsePositiveRateWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModerationStatsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\ModeratorPerformanceWidget;
use He4rt\PanelAdmin\Moderation\Widgets\RecentActionsWidget;
use He4rt\PanelAdmin\Moderation\Widgets\TopViolationTypesChartWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($user);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
});

test('dashboard page renders successfully', function (): void {
    $this->get(ModerationDashboard::getUrl())->assertSuccessful();
});

test('dashboard registers all widgets', function (): void {
    $page = new ModerationDashboard();

    expect($page->getWidgets())->toEqual([
        ModerationStatsWidget::class,
        CasesByStatusChartWidget::class,
        CasesByPlatformChartWidget::class,
        TopViolationTypesChartWidget::class,
        FalsePositiveRateWidget::class,
        ModeratorPerformanceWidget::class,
        AppealSlaWidget::class,
        RecentActionsWidget::class,
    ]);
});

test('stats widget shows correct pending count', function (): void {
    $tenant = Filament::getTenant();

    ModerationCase::factory()
        ->count(3)
        ->create([
            'status' => 'pending',
            'tenant_id' => $tenant->id,
        ]);

    ModerationCase::factory()
        ->resolved()
        ->create([
            'tenant_id' => $tenant->id,
        ]);

    livewire(ModerationStatsWidget::class)->assertSeeText('3');
});

test('stats widget handles empty state', function (): void {
    livewire(ModerationStatsWidget::class)->assertSeeText('0');
});

test('cases by status chart widget renders', function (): void {
    ModerationCase::factory()
        ->count(2)
        ->create(['status' => 'pending']);
    ModerationCase::factory()->resolved()->create();

    livewire(CasesByStatusChartWidget::class)->assertSuccessful();
});

test('cases by platform chart widget renders', function (): void {
    ModerationCase::factory()->create(['source_platform' => 'discord']);
    ModerationCase::factory()->create(['source_platform' => 'web']);

    livewire(CasesByPlatformChartWidget::class)->assertSuccessful();
});

test('top violations chart widget renders', function (): void {
    ModerationCase::factory()->create(['violation_type' => 'spam']);
    ModerationCase::factory()->create(['violation_type' => 'toxicity']);

    livewire(TopViolationTypesChartWidget::class)->assertSuccessful();
});

test('false positive rate widget renders', function (): void {
    ModerationCase::factory()->create(['status' => 'dismissed']);
    ModerationCase::factory()->resolved()->create();

    livewire(FalsePositiveRateWidget::class)->assertSuccessful();
});

test('moderator performance widget renders', function (): void {
    $moderator = User::factory()->create();

    ModerationAction::factory()->create(['moderator_id' => $moderator->id]);

    livewire(ModeratorPerformanceWidget::class)->assertSuccessful();
});

test('appeal sla widget shows active appeals', function (): void {
    ModerationAppeal::factory()->create([
        'status' => 'pending',
        'sla_deadline' => now()->addHours(30),
    ]);

    livewire(AppealSlaWidget::class)->assertSuccessful();
});

test('recent actions widget renders with period filter', function (): void {
    ModerationAction::factory()->create();

    livewire(RecentActionsWidget::class)->assertSuccessful();
});
```

- [ ] **Step 2: Run all tests**

```bash
php artisan test --compact --filter=ModerationDashboard
```

Expected: All tests pass.

- [ ] **Step 3: Run Pint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/tests/Feature/Moderation/ModerationDashboardTest.php
git commit -m "test(moderation): add dashboard widget feature tests"
```

---

### Task 13: Run full moderation test suite

- [ ] **Step 1: Run all moderation tests**

```bash
php artisan test --compact --filter=Moderation
```

Expected: All tests pass (previous 136 + new dashboard tests).

- [ ] **Step 2: Run Pint on all dirty files**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Final commit if any formatting fixes**

```bash
git add -A
git commit -m "chore(moderation): pint formatting cleanup"
```
