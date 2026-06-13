# ADR-0002: Minimal single-table raw lake (defer all modeling)

**Status:** Accepted
**Date:** 2026-06-06
**Deciders:** Clinton (Clintonrocha98)
**Supersedes:** [ADR-0001](0001-data-lake-approach.md) (structural part only)

> **Update (2026-06-06, pós-review #273):** dois refinamentos saídos da revisão do PR. (1) A tabela
> foi renomeada `whatsapp_events` → **`whatsapp_event_logs`**, alinhando com o irmão `discord_event_logs`.
> (2) A coluna `occurred_at` foi **removida**: no ingest ela era apenas `now()`, redundante com
> `received_at` e sem refletir o tempo de origem; o timestamp real do evento permanece no `payload`
> e é derivável (schema-on-read). `received_at` (`timestamptz`) é o único timestamp de domínio
> materializado.

## Context

ADR-0001 committed to a data lake but still shipped a fair amount of **modeling ahead of need**:
three tables (`whatsapp_groups`, `whatsapp_participants`, `whatsapp_events`), a membership pivot
(`whatsapp_group_participants`) with soft-delete and admin roles, a `GroupsMetadataHandler` Strategy,
lifecycle columns (`first_seen_at`/`last_seen_at`/`joined_at`/`left_at`), an `identity_id` scaffold,
`tenant_id` scoping, and several materialized envelope fields.

On the collector side (`wpp-tui`, PR #1), the tech-lead's review was explicit: _"O bot em si não
deveria parsear nenhum tipo de mensagem. Apenas enviar o conteúdo pra próxima endpoint e o back-end
em PHP/Laravel vai tratar e entender o quê fazer com o dado bruto."_ The collector was also doing
per-event-type work (a `match` building a deterministic UUIDv5 `event_id` from type-specific
canonical strings).

The phase is still **mapping/exploration** — the team does not yet know which engagement metrics
matter. Any entity table, role, or lifecycle column is a guess about what to do with the data, and
the raw payload already contains everything needed to derive those later. So the modeling was
premature on both sides.

## Decision

**Store one raw event per row in a single `whatsapp_event_logs` table.** Defer every entity, relationship,
and metric until the team knows what it wants — they are all derivable from the retained raw payload
via SQL when needed.

### Storage

`whatsapp_event_logs`:

| Column        | Type              | Notes                                                   |
| ------------- | ----------------- | ------------------------------------------------------- |
| `id`          | uuid (PK)         | UUIDv7 via the **native** `HasUuids` trait              |
| `event_id`    | uuid (UNIQUE)     | Idempotency key supplied by the collector               |
| `type`        | string (index)    | Baileys event name (only the collector knows it)        |
| `chat_jid`    | string? (index)   | Group JID; null tolerated                               |
| `received_at` | timestamptz (idx) | Stamped by the backend (`now()`); only domain timestamp |
| `payload`     | jsonb             | The **raw** Baileys event, untouched                    |
| timestamps    | tz                |                                                         |

Dropped vs ADR-0001: `whatsapp_groups`, `whatsapp_participants`, `whatsapp_group_participants`,
`GroupsMetadataHandler`, `EventHandler`/Strategy, the custom `HasVersion7Uuids` trait, `tenant_id`,
`group_id`/`participant_id` FKs, `participant_alt`, `occurred_at_source`, `display_name`, `push_name`,
`admin_role`, lifecycle columns, and `identity_id`.

### Wire contract

- The collector POSTs `{ type, chat_jid, payload }` with headers `X-Signature` (HMAC-SHA256 of the
  raw body) and `X-Event-Id`.
- **`event_id` is a random UUID minted once per collector outbox row and reused across HTTP retries.**
  It exists only for retry idempotency; the backend trusts it and deduplicates (`firstOrCreate`). This
  removes the per-event-type `match` from the collector — no content interpretation on the bot.
- The backend derives nothing structural from the payload at ingest beyond passing `type`/`chat_jid`
  through; any further extraction is a read-time concern.

### Edge filtering (collector)

Scope and noise control stay at the edge — this is selection, not parsing:

- **Groups only** (`@g.us`); DMs never leave the device.
- **Deny session/bulk events**: `creds.update`, `messaging-history.set`, `chats.set`,
  `contacts.set`, `blocklist.set`.
- **Deny the two firehoses for this phase**: `message-receipt.update` and `presence.update`.
  Re-enabling is a one-line denylist change (no backfill for the disabled window).

### Group structure (kept raw, not processed)

The collector keeps emitting `groups.metadata` snapshots (active fetch on connect) plus the passive
`group-participants.update` / `groups.update` deltas. All are stored as raw events with **no handler**.
Membership/roster/roles are derived later from snapshot + deltas. Membership has no backfill, so
capturing the baseline now matters.

## Consequences

### Positive

- **Genuinely dumb collector**: forwards raw, looks only at the JID (for the groups-only filter).
- **Trivial ingest**: webhook → dedup → `firstOrCreate(raw)`. Nothing to maintain while requirements
  are unknown.
- **No premature schema**: zero guesses about entities/metrics baked into migrations.
- **Smaller PRs**: both #273 and #1 shrink substantially, easier to review.

### Negative / deferred

- **Read-time digging**: deriving roster/metrics means SQL over `payload` jsonb (expression indexes if
  a query gets hot). Acceptable for exploration.
- **`event_id` is not content-deterministic**: a genuine Baileys re-emit (rare, e.g. reconnect replay)
  is stored as a duplicate. Tolerable for a capture-everything lake; dedup at read-time on the
  payload's own message id if needed.

### Review trigger

Same as ADR-0001: revisit when the data team defines the metrics worth keeping. At that point the raw
lake is the input from which a purpose-built model is derived.
