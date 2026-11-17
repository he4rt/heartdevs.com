---
paths:
  - '**/*.php'
---

# General — PHP

## Code comments — earn the line or delete it

A comment is not free: it competes for the reader's attention and the agent's context, and nothing verifies it. A stale comment is worse than none — a human discounts it, an agent obeys it. Before writing or keeping one, ask two things: when the code next to it changes, does this comment become a lie? And if I delete it, what breaks?

Earns a place:

- **Invariants** — the rule the code obeys, stated so a future edit that breaks it looks wrong.
- **Traps** — why the obvious thing is not done here.
- **Anchors** — `{@see Class::method()}`, `ADR-NNNN`, or a `CONTEXT.md` term. One line, a pointer, no copied reasoning.
- **Remote contracts** — a coupling the reader cannot see from this file.

Never earns a place: changelog (git holds it), accountability (the PR holds it), reasoning copied from an ADR (point at it), or narration that restates the line below. Default to zero inline comments. Prefer PHPDoc over inline. Write a comment only for a real trap or invariant.

## Never reference a tracker number in source

Banned in every comment and docblock: `issue #N`, `PR #N`, `map #N`, a bare `(#N)`, and `TODO (#N)`. An issue lives outside the repo — it closes, gets superseded, gets renumbered in memory, and a year later the number is dead and costs a round trip to resolve. An ADR is the opposite: versioned in the repo, immutable by convention, reviewed in the same diff as the code it governs. If a decision matters enough to be a comment, it matters enough to be an ADR — write it to `docs/adr/` (or `app-modules/<module>/docs/adr/`) and leave only the pointer. Pending work is a tracker concern: open the issue and let the tracker hold it.

## Replanning rewrites a comment, never stacks

When behaviour changes, delete the old comment and write the new state as if it had always been so. Never append a paragraph explaining why the one above it is now outdated. Deleting a comment that no longer holds is not destructive — leaving it is.

## An invariant belongs in a test before prose

A rule you would spell out in a long docblock is a rule a test should assert by name. A failing test is documentation that cannot go stale. Write the test first, then keep the comment only for the *why* the test name cannot carry — one or two lines.

## Comment language: English

Comments are written in English. Keep verbatim, never reworded: identifiers (class, method, property, column, config key, env var), test names quoted as evidence, and ADR titles or `CONTEXT.md` terms. A comment in another language converts to English when the rule governing it is rewritten — not as a separate translation pass.

## Comment style: short, one idea, active

Comment prose follows ASD-STE100 (Simplified Technical English) and Zinsser's four principles — simplicity, brevity, clarity, humanity:

- Short sentences — one clause where one clause carries the idea.
- One idea per sentence; split a sentence that carries two.
- Active voice — "the job re-reads the resource", not "the resource is re-read".
- One name per concept — the glossary's name, no synonyms.

## PHPDoc tags are not comments

This rule governs prose. Structural PHPDoc is mandatory and unaffected: `@property` blocks on models (see `.ai/rules/models.md`), `@param` / `@return` array shapes, `@var`, and generics. Keep them complete — PHPStan reads them.

## Dates keep their timezone

The app runs in UTC (`APP_TIMEZONE=UTC`). People read dates in `config('app.display_timezone')`.

- Application logic uses `now()` and `Carbon::now()`, which are UTC. Never set a timezone in logic: no `now()->timezone('America/Sao_Paulo')`.
- A Data class sends a date as ISO 8601 (`$user->created_at->toIso8601String()`). The page formats it with `formatDate()` or `formatDateTime()` from `@/lib/format`, in the viewer's timezone.
- Text that the server renders (a mail, an export) converts once: `$date->timezone(config('app.display_timezone'))->format('d/m/Y H:i')`.
- Raw SQL converts a `timestamptz` with one `AT TIME ZONE`: `occurred_at AT TIME ZONE 'America/Sao_Paulo'`. Two of them (`AT TIME ZONE 'UTC' AT TIME ZONE ...`) shift the time by 3 hours.
- Never set `'timezone' => 'UTC'` on the pgsql connection in `config/database.php`. It converts `timestamptz` columns twice.
