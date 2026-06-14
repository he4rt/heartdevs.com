# Integration WhatsApp Context

Ingest layer for the WhatsApp platform. Receives raw events from an external collector service
(`wpp-tui`, a separate Node/Baileys repo) via an HMAC-authenticated webhook, and persists them
**verbatim** as a single-table raw event store (data lake) for later analysis.

> **Status:** **Minimal single-table lake**, now hardened for failure resistance. One row per raw
> event in `whatsapp_event_logs`, no entity/membership modeling. Current decisions:
> `docs/adr/0003-deterministic-id-and-synchronous-ingest.md` (deterministic `event_id` + synchronous
> ingest; supersedes the wire-contract/ingest parts of `0002`) on top of `0002` (single raw table,
> supersedes `0001`). The collector sends raw events and Laravel stores them raw; all modeling
> (groups, participants, membership, roles, metrics) is **derived later** from the payload.
> Deferred: Filament admin, identity-link flow, metric aggregation, governance/LGPD, security hardening.

## Glossary

| Term               | Definition                                                                                                                                                                        | Not to be confused with                                                             |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| **Collector**      | The external Node/Baileys service (`wpp-tui` repo) that holds the WhatsApp WebSocket session and emits raw events.                                                                | This module (the Laravel-side ingest, not the WhatsApp runtime).                    |
| **Webhook ingest** | The HTTP endpoint that validates HMAC (over `event_id`+body) + `X-Event-Id`, then **persists synchronously** via an Action.                                                       | Any data transformation (none — payload stored raw, sanitized only for `\0`/UTF-8). |
| **Event store**    | The single `whatsapp_event_logs` table holding raw Baileys payloads (jsonb), one row per event.                                                                                   | Aggregated metrics or entity tables (which do not exist — derived later).           |
| **`event_id`**     | **Deterministic** idempotency key (UUIDv5 per-type, by content) minted by the Collector. Same logical event → same id → dedups at origin (outbox) **and** sink (`firstOrCreate`). | The row PK `id` (a backend-generated UUIDv7).                                       |
| **Snapshot**       | A raw `groups.metadata` event capturing a group's roster/roles at a moment; stored raw, not processed.                                                                            | A membership table (there is none; reconstruct from snapshot + deltas).             |

## Architecture

```text
WhatsApp servers
   ↓ WebSocket (Baileys)
[ wpp-tui — external Node repo ]   ← runtime; filters (groups-only + denylist), deterministic event_id, forwards raw
   ↓ POST /api/webhooks/whatsapp   (X-Signature = HMAC(event_id + body), X-Event-Id = UUIDv5)
   ↓ body: { type, chat_jid, payload }
[ integration-whatsapp — THIS module ]
   ├─ Ingest/Http/Middleware/VerifyWhatsAppSignature  ← validate HMAC(event_id+body) + X-Event-Id
   ├─ Ingest/Http/Controllers/WhatsAppWebhookController ← validate → Action (SYNC) → 2xx após commit
   ├─ Actions/StoreWhatsAppEventAction                 ← sanitize + firstOrCreate(event_id), stamps received_at
   └─ Models/WhatsAppEventLog                          ← single table, jsonb payload
   (sem fila no ingest; Redis é app-wide p/ moderation/discord, não p/ o lake)
        ↓ (future, read-time)
   derive groups/roster/roles/metrics via SQL over payload, when the data team defines them
```

## Module Boundaries

### This module owns:

- The webhook ingest endpoint and its HMAC/idempotency validation
- The single raw event store (`whatsapp_event_logs`)

### This module does NOT own:

- The WhatsApp WebSocket connection / Baileys runtime (lives in the `wpp-tui` repo)
- Any entity modeling (groups, participants, membership) — deferred; derived from raw later
- Metric aggregation / dashboards (deferred — likely `activity` + `panel-admin`)
- Identity/user records (belongs to `identity`)

## Dependencies

- **Identity** — participant ↔ user linking (future)
- **No dependency on** `bot-discord`, `moderation`, `community`, etc.

## Key decisions (see docs/adr/0002)

- **Single raw table**: store the raw Baileys payload in `whatsapp_event_logs.payload` (jsonb), one row
  per event. Materialize only `event_id`, `type`, `chat_jid`, `received_at` for
  indexing. Derive everything else (including the source timestamp) later.
- **`event_id` is a deterministic idempotency key** (UUIDv5 per-type, by content) from the collector.
  Dedups at origin (outbox `INSERT OR IGNORE`) **and** sink (`firstOrCreate`) — see ADR-0003.
- **Ingest is synchronous**: validate → sanitize (`\0`/UTF-8) → `firstOrCreate` → `2xx` only after commit.
  No ingest queue; the collector's durable outbox is the buffer. Failure-resistant regardless of queue driver.
- **Edge filtering, not parsing**: groups-only + deny session/bulk + deny the two firehoses
  (`presence.update`, `message-receipt.update`) for this phase.
- **Group snapshots kept raw**: `groups.metadata` + participant/metadata deltas are stored raw with
  no handler; membership is reconstructed later.
