# Filament Conventions These rules are project-specific and override the generic Filament guidelines from Boost when
they conflict. ## Action notifications: never send manually inside `action()` When an action (`Action`, `BulkAction`,
`DeleteAction`, etc.) needs a custom success notification with dynamic data — like a record count, a generated ID, or a
translated message — **declare it via `successNotification()` with a closure**, not by calling
`Notification::make()->send()` inside the `action()` callback. ### Why Filament built-in actions (`DeleteAction`,
`DeleteBulkAction`, `RestoreAction`, `ForceDeleteAction`, `EditAction`, `CreateAction`, etc.) **already dispatch a
default success notification** after `action()` returns. If you also call `Notification::make()->send()` inside
`action()`, the user sees **two notifications stacked**: the default one ("Deleted") and the custom one. The default
cannot be suppressed by the closure — it must be customized or disabled at configuration time.
`successNotification(Closure)` is the right hook because: 1. It **replaces** the default notification instead of adding
to it — no duplicates. 2. The closure runs **after** `action()` completes, so it can reflect the final state. 3.
Filament **injects utilities** into the closure (`$record`, `$records`, `$data`, etc.), so the dynamic count/title is
computed from the actual processed records, not from a stale variable captured at action-call time. 4. It separates
concerns: `action()` does the work, `successNotification()` declares the feedback. ### Wrong ```php
DeleteBulkAction::make() ->action(function (Collection $records): void { Proxy::query()->whereIn('id',
$records->pluck('id'))->delete(); // ❌ This sends a SECOND notification on top of Filament's default "Deleted" one.
Notification::make() ->success() ->title(__('panel-admin::proxies.bulk_delete.success', ['count' => $records->count()]))
->send(); }) ``` ### Right ```php DeleteBulkAction::make() ->action(function (Collection $records): void { // ✅
action() only does the work — no notification logic. Proxy::query()->whereIn('id', $records->pluck('id'))->delete(); })
->successNotification(fn (Collection $records): Notification => Notification::make() ->success()
->title(__('panel-admin::proxies.bulk_delete.success', [ 'count' => $records->count(), ])))
->deselectRecordsAfterCompletion() ``` ### When `Notification::make()->send()` *is* acceptable Custom actions that
**inherit directly from `Filament\Actions\Action`** (not from a built-in subclass like `DeleteAction`) do not have a
default success notification. For these, you may either: - Send a notification manually inside `action()` — fine because
there is no default to conflict with. - Or still prefer `successNotification()` for consistency, especially if other
actions in the same resource use that pattern. Reference example:
`app-modules/panel-admin/src/Filament/Resources/Proxies/Actions/BulkDeleteProxiesAction.php` is a custom `Action`
subclass and sends manually — that's fine. The table-level `DeleteBulkAction::make()` in `ProxiesTable.php` uses
`successNotification()` because it extends a built-in. ### Migration checklist When auditing existing actions in this
codebase, the smell is: a built-in action subclass (`DeleteAction`, `DeleteBulkAction`, `RestoreAction`,
`RestoreBulkAction`, `ForceDeleteAction`, `EditAction`, `CreateAction`, `ReplicateAction`) with
`Notification::make()->send()` inside its `action()` callback. If you find one, refactor it to use
`successNotification(Closure)` and remove the manual send. If the goal is just to **disable** the default notification
(no replacement needed), use `->successNotification(null)` instead of leaving `Notification::make()->send()` unmoved.
