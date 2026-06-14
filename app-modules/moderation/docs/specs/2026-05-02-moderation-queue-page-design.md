# Moderation Queue Page — Inbox UI with Flux UI

## Context

The moderation system has a Filament table-based `ListModerationCases` page that works for basic case listing, but moderators need a more efficient workflow for triaging cases — an inbox-style split-panel UI where they can quickly scan, select, and act on cases without navigating between pages.

This spec defines a new **custom Filament Page** inside the ModerationCluster (`/mod/queue`) that hosts a **Livewire component** using **Flux UI** for the interactive inbox layout. The existing `ListModerationCases` table view remains as a fallback.

## Architecture

**Approach: Full Livewire Component hosted in Filament Page.**

```
ModerationQueuePage (Filament Page)
  └── view: panel-admin::moderation.queue
        └── <livewire:moderation-queue />  (wire:poll.30s)
              ├── Filters (flux:select components)
              ├── CaseList (left panel, 35%)
              │     └── CaseListItem (blade partial)
              └── CaseDetail (right panel, 65%)
                    ├── Header
                    ├── SuggestedAction banner
                    ├── ContentSnapshot
                    ├── AiScores
                    ├── ReportsList
                    ├── UserHistory
                    └── Action buttons → Filament Action modals
```

The Filament Page provides cluster navigation and admin chrome. The Livewire component handles all interactive state (filters, selection, polling). Filament Actions handle modals for case resolution.

## Files to Create

```
app-modules/panel-admin/
├── src/Moderation/
│   ├── Pages/
│   │   └── ModerationQueuePage.php          ← Filament Page (host)
│   └── Livewire/
│       └── ModerationQueue.php              ← Livewire component
│
├── resources/views/moderation/
│   ├── queue.blade.php                      ← Filament Page view
│   └── queue/
│       ├── index.blade.php                  ← Livewire component view
│       ├── case-list-item.blade.php         ← Partial: list item
│       └── case-detail.blade.php            ← Partial: right panel
│
└── lang/
    ├── en/moderation.php                    ← Add queue translations
    └── pt_BR/moderation.php
```

## Files to Modify

- `app-modules/panel-admin/src/PanelAdminServiceProvider.php` — Register Livewire component

## Component: ModerationQueuePage

Filament Page that belongs to ModerationCluster. Renders the blade view that hosts the Livewire component.

- **Cluster:** `ModerationCluster::class`
- **Navigation:** icon=OutlinedInboxStack, label="Queue", group="Moderação", sort=0
- **Slug:** `queue`
- **View:** `panel-admin::moderation.queue`

The view is minimal — just `<x-filament-panels::page>` wrapping `<livewire:moderation-queue />`.

## Component: ModerationQueue (Livewire)

### Traits & Interfaces

```php
class ModerationQueue extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
}
```

### Properties

| Property           | Type      | Default     | Purpose                                  |
| ------------------ | --------- | ----------- | ---------------------------------------- |
| `$statusFilter`    | `string`  | `'pending'` | Filter by CaseStatus value or `'all'`    |
| `$platformFilter`  | `string`  | `'all'`     | Filter by Platform value or `'all'`      |
| `$violationFilter` | `string`  | `'all'`     | Filter by ViolationType value or `'all'` |
| `$severityFilter`  | `string`  | `'all'`     | Filter by Severity value or `'all'`      |
| `$selectedCaseId`  | `?string` | `null`      | UUID of selected case                    |

### Computed Properties

**`cases(): Collection`**

- Query: `ModerationCase::query()` with conditional `where` clauses per active filter
- Eager load: `author`, `reports`
- Order: `priority desc`
- Limit: 50 records
- Returns: `Illuminate\Support\Collection` of ModerationCase

**`selectedCase(): ?ModerationCase`**

- When `$selectedCaseId` is set: load with `author`, `reports.reporter`, `actions.moderator`
- When null: return null
- Separate query from `cases()` to load deeper relations only for the selected case

### Methods

**`mount(): void`**

- Auto-select first case from initial query

**`selectCase(string $id): void`**

- Set `$selectedCaseId = $id`

**`updated*Filter(): void`** (4 methods, one per filter)

- Reset `$selectedCaseId` to first case in new filtered results
- Clear computed cache

**`advanceToNextCase(): void`** (private)

- Called after successful action (take action, escalate, dismiss)
- Find next case in current `cases()` list after the resolved one
- If no more cases, set `$selectedCaseId = null`

### Filament Actions

**`takeActionAction(): Action`**

- Label: trans key `panel-admin::moderation.queue.actions.take_action`
- Icon: `Heroicon::OutlinedBolt`, Color: `danger`
- Visible: only when `selectedCase->status->isOpen()`
- Schema:
    - `Select action_type` → `ActionType::class` (required)
    - `Select duration` → `['24h', '7d', '30d', 'permanent']`
    - `CheckboxList target_platforms` → `Platform::class` (required)
    - `Textarea reason` (required)
- Action handler:
    1. Create `ModerationAction` with form data + `moderator_id = auth()->id()`, `tenant_id`, `automated = false`
    2. Dispatch `ExecuteAction` job (sync)
    3. Call `advanceToNextCase()`
    4. Send success notification

**`escalateAction(): Action`**

- Label: trans key `panel-admin::moderation.queue.actions.escalate`
- Icon: `Heroicon::OutlinedArrowUpCircle`, Color: `warning`
- Requires confirmation
- Action handler:
    1. Update case: `status = Escalated`
    2. Call `advanceToNextCase()`
    3. Send success notification

**`dismissAction(): Action`**

- Label: trans key `panel-admin::moderation.queue.actions.dismiss`
- Icon: `Heroicon::OutlinedXCircle`, Color: `gray`
- Requires confirmation
- Action handler:
    1. Update case: `status = Dismissed`, `resolved_at = now()`
    2. Call `advanceToNextCase()`
    3. Send success notification

## Layout: Inbox Split Panel

### Filters Bar

Row of `<flux:select>` components at the top, each bound to a filter property with `wire:model.live`:

- Status: all + CaseStatus enum options
- Platform: all + Platform enum options
- Violation: all + ViolationType enum options
- Severity: all + Severity enum options

Case count displayed on the right side of the filter bar.

### Left Panel — Case List (35% width, min 340px, max 440px)

Header row: "{count} caso(s)" + "Prioridade ↓" label.

Each `case-list-item` partial displays:

- **Row 1:** Priority badge (color-coded: ≥90 red, ≥70 amber, else gray) + violation type label + status pill + time ago
- **Row 2:** Content preview from `content_snapshot.text` — 2 lines max, clamped with CSS
- **Row 3:** Platform icon + `@author.name` + reports count (if > 0) + AI top score badge (color: ≥0.7 red, ≥0.4 amber, else green)

Selected item highlighted with primary color background. Click triggers `wire:click="selectCase('{{ $case->id }}')"`.

Scrollable list within fixed height container.

### Right Panel — Case Detail (65% width)

When a case is selected (`$selectedCaseId` is set):

**Header:**

- Title: `{violationType label} — @{author.name}`
- Badges: priority, status
- Meta: case ID (monospace), platform icon+label, time ago, reports count

**Suggested Action Banner** (conditional: when `suggested_action` is set and status is Pending):

- Amber background card with lightbulb icon
- Shows: suggested action label, prior offenses count, matched rules

**Two-column grid (1fr 360px):**

Left column:

- **Content Snapshot:** monospace block with `content_snapshot.text`
- **AI Scores:** grid of score cards, each showing category name + score value (color-coded)
- **Reports List:** list of reports with reporter name, reason, details

Right column:

- **User History:** author info, infraction count, past actions summary (from `actions` relation on author's past cases)
- **Action Buttons:** Filament Action buttons (Take Action, Escalate, Dismiss) — full width, stacked

When no case is selected: empty state with "Nenhum caso selecionado" message.

**`<x-filament-actions::modals />`** at the end of the component view for modal rendering.

### Polling

`wire:poll.30s` on the root div of the Livewire component view. Re-queries the `cases()` computed property on each tick. If the currently selected case was resolved by another moderator, `advanceToNextCase()` handles it gracefully.

## Registration

In `PanelAdminServiceProvider::register()`, add Livewire component registration:

```php
Livewire::component('moderation-queue', ModerationQueue::class);
```

The `ModerationQueuePage` is auto-discovered by the existing `discoverPages()` call on the `Moderation/Pages` directory.

## Translations

Add to `lang/en/moderation.php` and `lang/pt_BR/moderation.php`:

```php
'queue' => [
    'navigation_label' => 'Queue',
    'heading' => 'Moderation Queue',
    'cases_count' => ':count case(s)',
    'priority_sort' => 'Priority ↓',
    'no_cases' => 'No cases found',
    'no_case_selected' => 'No case selected',
    'no_case_selected_subtitle' => 'Select a case from the list to see details.',
    'filters' => [
        'status' => 'Status',
        'platform' => 'Platform',
        'violation' => 'Violation',
        'severity' => 'Severity',
        'all' => 'All',
    ],
    'detail' => [
        'content' => 'Content',
        'ai_scores' => 'AI Scores',
        'reports' => 'Reports',
        'author' => 'Author',
        'member_since' => 'Member since :date',
        'infractions' => 'Infractions',
        'past_actions' => 'Past Actions',
        'suggested_action' => 'Suggestion: :action',
        'prior_offenses' => ':count prior offense(s) in the last 30 days',
        'no_history' => 'Clean record',
        'matched_rules' => 'Rules: :rules',
    ],
    'actions' => [
        'take_action' => 'Take Action',
        'escalate' => 'Escalate',
        'dismiss' => 'Dismiss',
        'success' => 'Action executed successfully',
        'escalated' => 'Case escalated',
        'dismissed' => 'Case dismissed',
    ],
],
```

## Verification

1. **Navigate** to `/admin/{tenant}/mod/queue` — page loads within Filament cluster with correct navigation
2. **Filters:** change each filter → list updates, selection resets to first case
3. **Case selection:** click different cases → right panel updates with correct data
4. **Take Action:** click button → Filament modal opens → fill form → submit → case resolved, next case selected, success notification
5. **Escalate:** click → confirmation modal → confirm → case escalated, next case selected
6. **Dismiss:** click → confirmation modal → confirm → case dismissed, next case selected
7. **Polling:** wait 30s → list refreshes (verify with database insert)
8. **Empty states:** filter to get zero results → empty list message shown. No case selected → empty detail message
9. **Run tests:** `php artisan test --compact --filter=ModerationQueue`
