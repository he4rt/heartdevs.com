# Moderation Dashboard Widgets — Design Spec

## Overview

Replace the current minimal `ModerationDashboard` page (2 widgets) with a full analytics dashboard containing 8 widgets, a global time period filter, and real-time data queries.

## Architecture Decision

**Switch `ModerationDashboard` from `extends Page` to `extends Dashboard`.**

`Filament\Pages\Dashboard` extends `Page` — the `$cluster` property is inherited and works unchanged. This gives us native support for `HasFiltersForm` + `InteractsWithPageFilters` on all widgets.

## Global Filter

**Type:** `HasFiltersForm` (inline, always visible)

The dashboard page uses the `HasFiltersForm` trait to display a form at the top with:

- `Select` for time period preset: "Last 7 days", "Last 30 days", "Last 90 days", "This month", "This year"

All widgets use the `InteractsWithPageFilters` trait and read `$this->pageFilters['period']` to scope their queries. A shared helper method on each widget converts the period preset to a `Carbon` start date.

```
 MODERATOR                            SYSTEM
  │                                     │
  │  👆 Selects "Last 30 days"          │
  │ ────────────────────────────────►   │
  │                                     │  HasFiltersForm: period='30d'
  │                                     │  Session: persists filter
  │                                     │  Livewire: re-renders all widgets
  │                                     │
  │    All 8 widgets update with        │
  │    data scoped to last 30 days      │
  │ ◄────────────────────────────────── │
```

## Widget Inventory

### Grid Layout (2 columns)

```
┌─────────────────────────────────────────────────────┐
│  [Period Filter: Last 30 days ▼]                    │  ← HasFiltersForm
├─────────────────────────────────────────────────────┤
│  ① Stats Overview (full width)                      │
│  ┌──────────┬──────────┬──────────┬──────────┐      │
│  │Pendentes │Resolvidos│Tempo Méd.│Taxa Appeal│     │
│  └──────────┴──────────┴──────────┴──────────┘      │
├────────────────────────┬────────────────────────────┤
│  ② Casos por Status    │  ③ Casos por Plataforma   │
│  (Doughnut Chart)      │  (Doughnut Chart)          │
├────────────────────────┬────────────────────────────┤
│  ④ Top Violações       │  ⑤ Taxa de Falso Positivo  │
│  (Bar Chart horizontal)│  (Stats Overview)           │
├────────────────────────┬────────────────────────────┤
│  ⑥ Performance Mods    │  ⑦ SLA Appeals             │
│  (Table Widget)        │  (Table Widget)             │
├─────────────────────────────────────────────────────┤
│  ⑧ Recent Actions (full width) — already exists     │
└─────────────────────────────────────────────────────┘
```

### ① ModerationStatsWidget

- **Type:** `StatsOverviewWidget`
- **Span:** `full`
- **Stats:**

| Stat           | Value      | Query                                                                                      | Description/Icon                                         |
| -------------- | ---------- | ------------------------------------------------------------------------------------------ | -------------------------------------------------------- |
| Pendentes      | count      | `ModerationCase::where('status', 'pending')->count()`                                      | icon: clock, color: warning                              |
| Resolvidos     | count      | `ModerationCase::where('status', 'resolved')->where('resolved_at', '>=', $start)->count()` | icon: check-circle, color: success, desc: "este período" |
| Tempo Médio    | minutes    | `AVG(resolved_at - created_at)` on resolved cases in period                                | icon: clock, color: info, desc: "min p/ resolver"        |
| Taxa de Appeal | percentage | `appeals_count / actions_count * 100` in period                                            | icon: scale, color: purple, desc: "das ações"            |

Each stat includes a sparkline `chart()` with the last 7 data points (daily counts within the period).

### ② CasesByStatusChartWidget

- **Type:** `DoughnutChartWidget`
- **Span:** `1`
- **Heading:** "Casos por Status"
- **Query:** `ModerationCase::selectRaw("status, count(*) as total")->where('created_at', '>=', $start)->groupBy('status')`
- **Data:** One dataset with counts per `CaseStatus` enum, colors from `CaseStatus::getColor()`
- **Labels:** Translated via `CaseStatus::getLabel()` + count + percentage in the label string

### ③ CasesByPlatformChartWidget

- **Type:** `DoughnutChartWidget`
- **Span:** `1`
- **Heading:** "Casos por Plataforma"
- **Query:** `ModerationCase::selectRaw("source_platform, count(*) as total")->where('created_at', '>=', $start)->groupBy('source_platform')`
- **Data:** One dataset with counts per `Platform` enum, colors from `Platform::getColor()`
- **Labels:** Translated via `Platform::getLabel()` + count + percentage

### ④ TopViolationTypesChartWidget

- **Type:** `BarChartWidget` (horizontal)
- **Span:** `1`
- **Heading:** "Top Tipos de Violação"
- **Query:** `ModerationCase::selectRaw("violation_type, count(*) as total")->whereNotNull('violation_type')->where('created_at', '>=', $start)->groupBy('violation_type')->orderByDesc('total')`
- **Data:** Horizontal bar chart. `indexAxis: 'y'` via `getOptions()`. Colors from `ViolationType::getColor()`.
- **Labels:** `ViolationType::getLabel()`

### ⑤ FalsePositiveRateWidget

- **Type:** `StatsOverviewWidget`
- **Span:** `1`
- **Heading:** "Taxa de Falso Positivo"
- **Stats:**

| Stat    | Value      | Query                                                      |
| ------- | ---------- | ---------------------------------------------------------- |
| FP Rate | percentage | `dismissed_cases / (dismissed + resolved) * 100` in period |

- **Description:** "↓ melhorando" or "↑ piorando" comparing current period vs previous period of same length
- **Additional stats:** One stat per classifier source (`CaseSource::AutoDetect`, `CaseSource::RuleMatch`) showing individual FP rate
    - Query: filter `ModerationCase` by `source` + dismissed vs total resolved

### ⑥ ModeratorPerformanceWidget

- **Type:** `TableWidget`
- **Span:** `1`
- **Heading:** "Performance dos Moderadores"
- **Query:** Aggregate `ModerationAction` grouped by `moderator_id` in period:
    - `total_cases`: `COUNT(*)`
    - `avg_resolution_time`: `AVG(moderation_actions.created_at - moderation_cases.created_at)` via join
    - `overturn_rate`: subquery counting appeals with `status = 'overturned'` / total actions
- **Columns:**
    - Avatar + username (from `User` relation)
    - Cases count
    - Average time (formatted as "Xmin")
    - Overturn rate (formatted as "X%")
- **Footer description:** "Appeals revertidos (geral): X% (saudável: <15%)"
- **Sorted by:** total cases descending
- **Paginated:** false (top moderators only, limit 10)

### ⑦ AppealSlaWidget

- **Type:** `TableWidget`
- **Span:** `1`
- **Heading:** "SLA de Appeals"
- **Query:** `ModerationAppeal::where('status', 'pending')->orWhere('status', 'reviewing')->orderBy('sla_deadline')`
- **Columns:**
    - SLA indicator: `IconColumn` — green if `sla_deadline > now()`, red if overdue
    - Appeal ID (short format)
    - Appellant name (from `appellant` relation)
    - Reviewer name (from `reviewer` relation), or "—" if unassigned
    - Time remaining: computed `sla_deadline - now()`, formatted as "Xh restantes" or "Xh atrasado"
- **Footer description:** "X appeals resolvidos dentro do SLA este mês (Y%)"
    - Query: `ModerationAppeal::where('resolved_at', '>=', $startOfMonth)->whereColumn('resolved_at', '<=', 'sla_deadline')->count()` vs total resolved
- **Paginated:** false (only active appeals, typically small count)

### ⑧ RecentActionsWidget (existing)

- **Type:** `TableWidget`
- **Span:** `full`
- Already implemented. Only change: add `InteractsWithPageFilters` trait to scope the query by the global period filter.

## Page Changes

### ModerationDashboard

**Before:**

```php
class ModerationDashboard extends Page
{
    // 2 widgets via getHeaderWidgets()
}
```

**After:**

```php
class ModerationDashboard extends Dashboard
{
    use HasFiltersForm;

    // $cluster, navigation properties unchanged

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Select::make('period')
                        ->options([
                            '7d' => 'Last 7 days',
                            '30d' => 'Last 30 days',
                            '90d' => 'Last 90 days',
                            'month' => 'This month',
                            'year' => 'This year',
                        ])
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

## Shared Pattern: Period Filter Resolution

All widgets that use `InteractsWithPageFilters` share the same period-to-date logic. Extract to a trait:

```php
trait ResolvesFilterPeriod
{
    protected function periodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
        };
    }

    protected function previousPeriodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(14),
            '30d' => now()->subDays(60),
            '90d' => now()->subDays(180),
            'month' => now()->subMonth()->startOfMonth(),
            'year' => now()->subYear()->startOfYear(),
        };
    }
}
```

## Data Flow

```
┌──────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ FilterForm   │────►│ $this->filters   │────►│ Livewire re-    │
│ (Select)     │     │ ['period'=>'30d']│     │ render widgets  │
└──────────────┘     └──────────────────┘     └────────┬────────┘
                                                       │
                     ┌─────────────────────────────────┘
                     ▼
        ┌────────────────────────┐
        │ Each widget reads      │
        │ $this->pageFilters     │
        │ via InteractsWithPage  │
        │ Filters trait          │
        ├────────────────────────┤
        │ ResolvesFilterPeriod   │
        │ →periodStart(): Carbon │
        ├────────────────────────┤
        │ Real-time DB query     │
        │ scoped by period       │
        └────────────────────────┘
```

## Files to Create/Modify

### New files (8):

1. `app-modules/panel-admin/src/Moderation/Widgets/ModerationStatsWidget.php`
2. `app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusChartWidget.php`
3. `app-modules/panel-admin/src/Moderation/Widgets/CasesByPlatformChartWidget.php`
4. `app-modules/panel-admin/src/Moderation/Widgets/TopViolationTypesChartWidget.php`
5. `app-modules/panel-admin/src/Moderation/Widgets/FalsePositiveRateWidget.php`
6. `app-modules/panel-admin/src/Moderation/Widgets/ModeratorPerformanceWidget.php`
7. `app-modules/panel-admin/src/Moderation/Widgets/AppealSlaWidget.php`
8. `app-modules/panel-admin/src/Moderation/Widgets/Concerns/ResolvesFilterPeriod.php`

### Modified files (3):

1. `app-modules/panel-admin/src/Moderation/Pages/ModerationDashboard.php` — switch to `extends Dashboard`, add `HasFiltersForm`, register all widgets
2. `app-modules/panel-admin/src/Moderation/Widgets/RecentActionsWidget.php` — add `InteractsWithPageFilters` + `ResolvesFilterPeriod`, scope query by period
3. `app-modules/panel-admin/src/Moderation/Widgets/CasesByStatusWidget.php` — **delete** (replaced by `ModerationStatsWidget` + `CasesByStatusChartWidget`)

### Translation files (2):

1. `app-modules/panel-admin/lang/en/moderation.php` — add dashboard widget keys
2. `app-modules/panel-admin/lang/pt_BR/moderation.php` — add dashboard widget keys

### Test files (1):

1. `app-modules/panel-admin/tests/Feature/Moderation/ModerationDashboardTest.php` — test page renders, filter works, widgets are present

## Testing Strategy

- **Page render test:** authenticated admin can access the dashboard, all 8 widgets render
- **Filter test:** changing period filter causes widgets to re-render (assert Livewire component updates)
- **Widget data tests:** for each widget, create seed data and verify correct counts/aggregations
- **Edge case:** empty state (no cases) — all widgets should handle zero data gracefully
