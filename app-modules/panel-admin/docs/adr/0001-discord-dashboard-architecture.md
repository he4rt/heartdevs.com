# ADR-0001: Discord Dashboard Architecture

**Status:** Accepted
**Date:** 2026-05-19
**Deciders:** danielhe4rt

## Context

The He4rt Developers community needs visibility into Discord activity patterns for two purposes:

1. **Scheduling events** — knowing when the community is most active (day-of-week × hour) to pick optimal times for lives, workshops, and meetings
2. **Measuring community health** — tracking whether engagement (messages, voice, unique users) is growing or declining over time

The data already exists in the `messages` and `voice_messages` tables (from the `activity` module), but there is no way to visualize trends, compare periods, or identify peak hours without running raw SQL.

## Decision

### Dashboard structure

A single Filament Page (`DiscordDashboard`) under a new `MarketingCluster`, with the following widgets top-to-bottom:

| Widget            | Purpose                                | Implementation                                                            |
| ----------------- | -------------------------------------- | ------------------------------------------------------------------------- |
| Stats overview    | Quick KPIs with sparklines             | Filament `StatsOverviewWidget` with `->chart()`                           |
| Period breakdown  | Rolling comparison between sub-periods | Custom Blade with 5 switchable views (summary, table, cards, bars, donut) |
| Activity timeline | Daily trend over the full range        | Chart.js line chart (msgs + voice + users)                                |
| Heatmap           | Peak hours identification              | `x-he4rt::dashboard.heatmap` component (SVG, week variant)                |
| Activity by DOW   | Best day-of-week for events            | `x-he4rt::dashboard.bar-chart` with stacked mode toggle (All/Msgs/Voice)  |
| Top channels      | Most active channels                   | `x-he4rt::dashboard.bar-chart` ranked                                     |

### Rolling comparison pattern

Instead of fixed calendar quarters, the selected time range is subdivided into equal sub-periods:

| Range | Subdivision |
| ----- | ----------- |
| 90d   | 3 × 30d     |
| 60d   | 2 × 30d     |
| 30d   | 4 × ~7d     |
| 14d   | 2 × 7d      |
| 7d    | 7 × 1d      |
| 1d    | 24 × 1h     |

**Why:** Rolling comparison adapts to any range and avoids the problem of incomplete quarters. Stats cards show last sub-period vs previous; period breakdown shows all sub-periods.

### Query layer

Each widget has a dedicated `final readonly` query class with a `builder()` method, located in `Marketing/Discord/Queries/`. The Page instantiates queries and passes results to the view. Heatmap data for messages and voice comes from separate query classes — the Page combines them.

`PeriodStats` is a single class with private methods per metric to keep cohesion while avoiding class explosion.

### Summary view design

The period breakdown's "summary" mode uses a 50/50 layout:

- **Left:** Line chart comparing sub-periods overlaid on the same axis (e.g. Mon→Sun with 4 lines: Msgs W1, Msgs W2, Voice W1, Voice W2). Solid = previous, dashed = current.
- **Right:** Narrative text blocks with trend icons, absolute values, and a contextual recommendation box that adapts based on the data pattern.

### Channel names deferred

Channel IDs are stored as Discord snowflakes with no name resolution. Dedicated Discord entity tables (channels, roles, members) are a separate future initiative. The dashboard shows channel IDs for now.

### Timezone

Fixed to `America/Sao_Paulo`. The community is Brazilian and there is no use case for other timezones. Consistent with the existing Meeting Showcase page.

### Component extensions

The `x-he4rt::dashboard.bar-chart` Blade component was extended with backward-compatible props:

- `stacked` (bool) — enables segmented bars
- `segments` (array per item) — defines bar segments with label, value, color
- `legend` (array) — renders a legend below the chart

All existing usages remain unaffected.

## Alternatives Considered

### A — Filament Widgets for everything

Use `ChartWidget` and `StatsOverviewWidget` for all visualizations. Rejected because: heatmap has no native widget, period breakdown needs 5 switchable views that don't map to any widget, and mixing native widgets with custom Blade creates inconsistent layout control.

### B — Fixed quarter comparison

Compare Q1 vs Q2 vs Q3 with calendar-aligned periods. Rejected because: the current quarter is always incomplete, making the comparison misleading. Rolling comparison is more flexible and avoids this problem.

### C — He4rt design system components for stats

Use `x-he4rt::dashboard.stat-card` instead of Filament's `StatsOverviewWidget`. Rejected because: Filament's widget has built-in sparklines via `->chart()`, lazy loading, and polling — reimplementing these in custom Blade would be wasted effort.

## Consequences

### Positive

- Community managers can identify peak hours at a glance (heatmap + DOW chart)
- Rolling comparison makes weekly/monthly trends visible without manual SQL
- The summary view with narrative text makes data accessible to non-technical team members
- Stacked bar-chart extension is reusable across other dashboards
- No new domain modules or models required — reads from existing tables

### Negative

- Chart.js is loaded via CDN (`@assets`) — adds an external dependency to the admin panel
- Charts inside `x-show` containers require `x-intersect` workarounds for Chart.js canvas sizing
- Channel IDs instead of names degrades UX until Discord entity tables are built

### Risks

- Voice data may be sparse or missing for some periods (observed: zero voice events in recent 14 days). The dashboard must handle empty datasets gracefully.
- Large time ranges (90d) on high-volume servers may produce slow heatmap queries. Mitigated by: indexed columns (`tenant_id, sent_at`), and query classes can add caching later without view changes.
