# Panel Admin — Context

The admin panel (`/admin`) is the operational hub for the He4rt Developers community. It is a Filament v5 panel that provides dashboards, resource management, and moderation tools for community administrators.

## Glossary

| Term                   | Definition                                                                                                                                                           | Not to be confused with                                |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| **Cluster**            | A Filament navigation group that acts as a sub-panel with its own sidebar. Implemented as a `Cluster` class with `$slug` and `$shouldRegisterSubNavigation = false`. | Filament NavigationGroup (simpler, no sub-navigation)  |
| **Marketing**          | Cluster focused on community growth analytics — Discord activity dashboards, meeting showcase. Slug: `marketing`.                                                    | The marketing team (people); here it's a panel section |
| **Discord Dashboard**  | A Filament Page under Marketing that surfaces Discord community metrics (messages, voice, users) with configurable time ranges and rolling comparisons.              | The Discord bot itself (`bot-discord` module)          |
| **Meeting Showcase**   | A Filament Page under Marketing that generates visual cards of meeting participants for social media sharing.                                                        | The `meeting` domain module (data layer)               |
| **Rolling comparison** | The pattern of subdividing a selected time range into equal sub-periods to show evolution. 14d→2×7d, 30d→4×7d, 90d→3×30d, etc.                                       | Quarter-based comparison (fixed calendar periods)      |
| **Period breakdown**   | A widget showing sub-period metrics side by side with multiple visualization modes (summary, table, cards, bars, donut).                                             | The timeline chart (shows daily granularity)           |
| **Activity by DOW**    | Aggregated activity per day-of-week (Mon→Sun) with toggle between All/Messages/Voice. Uses stacked bar chart in "All" mode.                                          | Heatmap (shows hour×day matrix)                        |

## Module boundaries

Panel Admin is a **view layer** module. It:

- **Reads from** `activity` (messages, voice), `identity` (external identities), `moderation` (cases, appeals, actions)
- **Never writes** domain data — only reads for display
- **Owns** its Filament Pages, Widgets, Resources, and Blade views
- **Does not** contain domain logic, models, or migrations

## Structure

```
panel-admin/
├── src/
│   ├── Marketing/
│   │   ├── MarketingCluster.php
│   │   ├── Pages/
│   │   │   ├── DiscordDashboard.php
│   │   │   └── MeetingShowcasePage.php
│   │   └── Widgets/
│   │       └── DiscordStatsWidget.php
│   ├── Moderation/
│   │   ├── ModerationCluster.php
│   │   ├── Pages/
│   │   ├── Resources/
│   │   ├── Widgets/
│   │   └── Livewire/
│   ├── Filament/Resources/
│   ├── Pages/
│   │   └── Dashboard.php
│   └── PanelAdminServiceProvider.php
├── resources/views/
│   ├── marketing/
│   └── moderation/
├── lang/{en,pt_BR}/
├── config/panel-admin.php
└── docs/adr/
```

## Navigation pattern

Each cluster has a dedicated navigation builder method in `PanelAdminServiceProvider`. When the URL path contains the cluster slug (e.g. `marketing/`), the sidebar switches to show a "Back to Admin" link + the cluster's sub-navigation. Default navigation shows all clusters as top-level items.
