# ADR-0001: Data-lake ingest for WhatsApp events (raw payload)

**Status:** Superseded by [ADR-0002](0002-minimal-single-table-lake.md) (2026-06-06)
**Date:** 2026-05-20
**Deciders:** Clinton (Clintonrocha98)

> **Superseded (2026-06-06):** the structural part of this decision (three tables
> `whatsapp_groups`/`whatsapp_participants`/`whatsapp_events`, materialized FKs, snapshot
> processing) was replaced by a **single raw table** in [ADR-0002](0002-minimal-single-table-lake.md).
> Read ADR-0002 for the current shape; this file is kept as the historical record of why the first
> design over-modeled.
>
> **Update (2026-05-21):** the per-group `collection_policy` mechanism mentioned in this ADR was
> **dropped** during implementation. The Collector sends every event and the Laravel side stores
> everything raw — no filtering, no `collection_policy` column, no `GET /policies` endpoint. The rest
> of this decision (data lake, collect all event types) stands. Revisit policy/filtering only if a
> hardening phase requires it.

## Context

The He4rt community runs three WhatsApp groups (Geral, He4rt Delas, Vagas) and wants to capture
member activity to eventually cross-reference with the unified He4rt profile. A separate Node/Baileys
service (`wpp-tui`) already holds the WhatsApp session and emits every event.

The open question was **how the Laravel monolith should model and store these events**. The main
tension: **we don't yet know which metrics matter.** The team has not defined what engagement signals
to compute. Designing a normalized, purpose-built schema now risks modeling the wrong things.

An earlier draft of the design proposed four normalized tables. During the brainstorming this was
deliberately reversed in favor of maximum exploration freedom for an initial mapping phase.

## Decision

Adopt a **data-lake (schema-on-read) ingest** with three tables, storing raw Baileys payloads:

- `whatsapp_groups`, `whatsapp_participants`, `whatsapp_events` (see `docs/spec.md` for full schema).
- `whatsapp_events.payload` (jsonb) holds the **complete, raw** Baileys event. Only `type`, the FKs,
  and timestamps are materialized as top-level columns for indexing.

Scoped to the **exploration phase**:

- **Collect all event types**, including high-volume noise like `presence.update`. The data team
  gets the broadest possible material to work with.

## Consequences

### Positive

- **Maximum exploration freedom.** The data team can derive any metric retroactively from raw payloads
  without re-instrumenting the collector.
- **Simple ingest.** The Laravel side is a thin webhook → Job → insert. No transformation logic to
  maintain while requirements are unknown.

### Negative / risks (must be revisited)

- **Volume grows unbounded.** All events (incl. `presence.update`) means `whatsapp_events` will grow
  quickly. Partitioning by `type` and/or by date may become necessary.

### Review trigger

This decision **must be revisited when the exploration phase ends** — concretely, once the data team
has defined the metrics worth keeping. At that point, evaluate:

- Aggregating useful signals into a persistent metrics table and **dropping or truncating raw payloads**.
- Whether `presence.update` is still worth collecting at volume.
