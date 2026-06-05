# ADR-0001: GitHub Community Contributions

**Status:** Accepted
**Date:** 2026-06-04
**Deciders:** Clintonrocha98

## Context

The community wanted a recurring presentation ("Quem fez a He4rt bater") showing who is contributing across the community's public GitHub repos. The first attempt was a provisional artisan command (`community:retrospective`) that ran `gh` live, aggregated a JSON in-memory and uploaded it to a paste host. It did not persist anything, ran outside the modular architecture (under `app/Retrospective/`), and only worked for a single repo at a time.

We need a persistent, queryable model that:

- Counts **all** contributors by GitHub login — whether or not they are registered He4rt members.
- Captures **history** (backfill) and **new activity** (live).
- Spans **multiple repos**, curated and maintainable without code changes.
- Lives inside the modular architecture and respects boundaries.

The existing `activity` domain has an `Interaction` model, but it is gamification-coupled: it requires a `character_id` (a registered member) and a `tenant_id`, and carries coins/xp. It cannot represent a GitHub contributor who never joined the platform.

## Decision

Build the ingestion in `integration-github` (transport), mirroring the Discord data-lake pattern.

### 1. Own contribution model, not `Interaction`

A normalized `github_contributions` table keyed by GitHub login/id (member-or-not), with `unique(repo, type, external_ref)` for idempotency. Gamification is left as a **seam**: this module emits `GithubContributionRecorded` and does not depend on `activity`/`economy`.

### 2. Normalized read model + raw lake

`github_contributions` is the read model the presentation queries. `github_event_logs` is the raw, append-only webhook lake (deduped by delivery id) for audit/replay — exactly mirroring `discord_event_logs`.

### 3. Split ingestion paths

- **Webhook (live):** org webhook → signature-verified → raw payload into the lake → `ProjectGithubEvent` projects into contributions.
- **Backfill (history):** paginated REST list requests upsert contributions directly. Resumable via `last_backfilled_at`. The `unique(repo, type, external_ref)` key makes both paths converge without duplicates.

### 4. Panel-managed allowlist

`github_repositories` (managed in `panel-admin`) is the source of truth for which repos count. The org webhook delivers everything; ingestion filters by the allowlist.

### 5. Store everything, filter on read

Everything is stored raw. The presentation excludes only **bots** at read time. **Closed-unmerged PRs count as participation** (consistent with the "all contributors" audience), but are broken out by outcome (`prs_merged` / `prs_unmerged`) so the view distinguishes work that landed from work that was rejected/abandoned. Because filtering lives only in the read model, this refinement was a one-function change with no re-backfill — exactly the payoff of storing everything raw.

### 6. Auth and scope

A fine-grained PAT (`services.github.api_token`) authenticates the REST connector for backfill; a manually-registered org webhook with a shared secret (`services.github.webhook_secret`) feeds the live stream. Contribution types in scope: PR, review, issue, comment, commit (no reactions — GitHub has no reaction webhook).

## Consequences

- **Positive:** the presentation is fast and queryable by period/person/type; history and live converge idempotently; gamification can be wired later without re-modeling; adding a repo is a panel action.
- **Negative:** PR size enrichment costs one extra request per PR (the dominant cost — ~2 requests/PR); reactions are out of scope; the org webhook delivers traffic for non-allowlisted repos (stored in the lake for audit, then ignored).
- **Backfill scale & resilience:** the backfill is **synchronous and rate-limit-aware** — every request uses Saloon `->throw()`, so a 403/5xx fails loudly (no silent corruption); on a rate-limit 403 both backfill transports (the `github:backfill` console command and the panel's "Backfill agora" action) surface a clear, actionable message and is safely resumable (idempotent upsert means a re-run after the reset costs nothing extra and never duplicates). The rate-limit interpretation lives in a single shared `Backfill\RateLimit` helper so the two transports cannot drift. At current scale this is enough: the main repo (~230 PRs) is ~500 requests, ~10% of the 5,000/hour budget. A **queue** (job-per-repo with release-on-limit) and a true **incremental** refresh (using `last_backfilled_at` as `since` — today it is recorded but each run still does full history) are deliberately deferred as YAGNI; revisit if many large repos are added.
- **Follow-up:** revisit a lake retention/TTL policy; decide whether commits without a linked GitHub user enter the per-person ranking; implement the gamification bridge via the seam event.
