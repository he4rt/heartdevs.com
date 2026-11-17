---
paths:
  - 'app/**/*.php'
  - 'app-modules/*/src/**/*.php'
---

# Logging

Every unit of work (an HTTP request, a queued job, an Artisan command, a scheduled closure, a call to a tool of the development MCP server) writes one wide event when it ends. The event collects context while the work runs. `App\Support\Logging` holds the core, the `events` channel writes JSONL to `storage/logs/events-YYYY-MM-DD.jsonl`, and a super admin reads it on **System → Logs**. The plan is `docs/plans/2026-11-13-logging.md`.

## Add context, never write a line

- Never call `Log::`, `logger()` or `info()`. `tests/Arch/ArchTest.php` fails on them outside `App\Support\Logging`.
- Add what explains the work to the open event with `WideEvent::add()`. When no unit is open, the call does nothing.

```php
// Avoid: a loose line with no request, user or outcome
Log::info('Card charged', ['order' => $order->id, 'total' => $order->total_cents]);

// Good: a field on the event of the request or the job
WideEvent::add('checkout.order_id', $order->id);
WideEvent::add('checkout.total_cents', $order->total_cents);
```

- A value is a scalar or null. Send an id, a count or a status, never an array, a model or a collection.

## Keys

- A key is dotted `snake_case`, grouped by the subject of the code that adds it: `checkout.total_cents`, `invite.role`.
- The core owns these keys. Business code never sets them: `timestamp`, `kind`, `event_id`, `request_id`, `service`, `environment`, `version`, `duration_ms`, `memory_peak_mb`, `outcome`, `user.id`, `team.id`, `impersonator.id`, and every key under `http.`, `job.`, `command.`, `schedule.`, `mcp.`, `error.`, `db.`, `feature_flags.` and `sampling.`.
- The same key always holds the same type, so a filter in the reader never compares a number with a string.

## What goes in an event

Follow `.ai/domain/04-data`: assume the file has leaked already.

- Send ids and business values only. Never an email, a name, an IP address, a token, a password, a session id or a request body.
- The core hides a value when the last segment of its key is sensitive (`ActivityRedactor::isSensitive()`, the same list as the activity log) and cuts a string at 500 characters. This is a net, not a permission: do not send the value.

## Errors

- Throw. The exception handler puts the first exception of the unit in `error.*`, with the frames of the app and the `context()` of the exception under `error.context`.
- An exception that has useful context implements `context()` with ids and values, never personal data:

```php
public function context(): array
{
    return ['order_id' => $this->order->id, 'gateway_code' => $this->code];
}
```

- The message of an exception never holds personal data. It goes into the event as it is.
- Never catch an exception only to log it. Catch it when the code recovers, and add what happened with `WideEvent::add()`.

## Sampling and retention

- The core keeps every error, every slow unit, every command, scheduled task and MCP tool call, every unit that read a Team or User flag in a rollout, and every unit of a user with the `keep-every-log` flag. It keeps a sample of the rest (`LOG_SAMPLE_RATE`).
- An event holds `user.id` and `team.id`. `DeleteUser` does not remove them: the `events_daily` channel deletes every file after `LOG_EVENTS_DAYS` (14) days, and that rotation is the deletion. Never raise it without a decision that says why.

## Tests

- Call `fakeWideEvents()` from `tests/Pest.php`. It returns a reader of the events that the unit wrote.
- A test of the console calls `resolve(Kernel::class)->rerouteSymfonyCommandEvents()` first. Laravel does not fire the command events in unit tests, so the test passes without the call and proves nothing.
