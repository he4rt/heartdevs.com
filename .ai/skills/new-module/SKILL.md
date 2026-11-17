---
name: new-module
description: Size a request and map it to modules before any code. Use before creating a folder in app-modules or running make:module, and when a request names a product area that no module owns ("an e-commerce", "billing", "a marketplace"). Interviews the user, writes PRODUCT.md (impeccable product schema), CONTEXT-MAP.md and docs/roadmap.md, and scaffolds only the modules of the first open phase.
argument-hint: <what the user wants to build>
---

# New Module

Follow `.ai/guidelines/domain/06-new-modules.blade.php`. This skill runs it in six steps. Never skip the approval gate in step 5.

```text
[1 Load] → [2 Explore] → [3 Interview] → [4 PRODUCT.md] → [5 Map + roadmap] → ✋ approval → [6 Scaffold phase 1]
```

## 1. Load the state

Read these files. Each one changes what you do next.

| File              | Missing                                                 | Present                                                                             |
| ----------------- | ------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `PRODUCT.md`      | Interview for the product (round 1), then write it.     | Ask only what is stale or missing. Never reopen a confirmed field without a reason. |
| `CONTEXT-MAP.md`  | Create it in step 5, with the modules that exist today. | Use it as the list of modules, planned and real.                                    |
| `docs/roadmap.md` | Create it in step 5.                                    | Add the new modules to a phase. Never reorder a phase that started.                 |

Also read the `CONTEXT.md` of each module that exists, and `.ai/guidelines/domain/02-module-architecture.blade.php`.

`PRODUCT.md` already describes the He4rt Hub. Treat its fields as confirmed: change one only when the user changes it in the interview.

## 2. Explore before you ask

Read the repo before the first question: modules and their models, routes, enums, `docs/admin/`, `README.md`, `composer.json`. Write down what you found as hypotheses. Repo evidence is a hypothesis, not the user's approval. Ask the user to confirm it.

## 3. Interview

Use the question tool. Ask at most three questions per round, only about real gaps, and show what you found when it helps the user answer. Keep going until every capability has one owner.

**Round 1, the product** (only when `PRODUCT.md` is missing or stale). These are the impeccable `init` questions:

1. Who is the primary user, in what situation, and what job are they doing?
2. What does the product make possible, and what is its different mechanism or position?
3. What durable constraints, assets, evidence or product facts must future work keep?

Never ask about colours, fonts or visual style here. That is design work, not product truth.

**Round 2, the capabilities.** List the capabilities that the answers imply and let the user confirm, cut or add (multi-select). Then ask which ones the first release needs. For "an e-commerce":

```text
  ☑ catalog      products, variants, prices
  ☑ cart         a basket that becomes an order
  ☑ orders       order lifecycle: placed → paid → shipped → delivered
  ☑ payments     a payment provider
  ☐ shipping     carriers and tracking          (not in the first release)
  ☐ reviews      ratings on a product          (cut)
```

**Round 3 and on, the boundaries.** For each capability, find the answers that decide its rung:

- Does a word mean something else here? A catalog "product" changes its price; an order "line" keeps the price it was sold at. Two meanings mean two modules.
- Does it have its own lifecycle (a state machine of its own)?
- Who owns its records? Can they exist without a record of another module?
- Is it scoped to a `Team` (see `domain/multi-tenancy`)?
- Does it call an external system? Which one, and is it needed now?

Stop when every capability sits on one rung of the ladder and every external system has an integration module or an explicit "not now".

## 4. Write PRODUCT.md

Write `PRODUCT.md` at the root with the template in `references/templates.md`. Rules:

- Write only confirmed facts. Mark an open decision as `Undecided:`, never fill it with a guess.
- Product language only: no module names, tables, classes or routes. Those go in `CONTEXT-MAP.md`.
- Omit a section that has nothing specific to say, instead of writing generic prose.
- Never invent customers, numbers, prices or claims. Write what is absent, so later work does not invent it.
- Never overwrite the file in silence. When it exists, show the diff of what changes.

## 5. Map the modules and plan the phases

1. Put each capability on the ladder. A capability on rung 1 or 2 names the module that takes it.
2. Write `CONTEXT-MAP.md` and `docs/roadmap.md` with the templates in `references/templates.md`. Planned modules get the status `planned`.
3. Check the dependencies against `domain/module-architecture`: a domain module never depends on a panel or an integration. A cycle between two domain modules means one boundary is wrong. Ask again.
4. Show the user the map and the phases as a diagram, and ask for approval.

✋ **Approval gate.** Do not scaffold until the user approves the map and the first phase. A change to the map goes back to step 3 for that capability.

## 6. Scaffold the first open phase

Scaffold only the modules of the first phase that has not started. For each one, in one change:

1. `php artisan make:module <slug> --no-interaction`. The stubs in `stubs/app-modules/` write `composer.json`, `phpstan.neon` and the service provider.
2. In the root `composer.json`: require `"he4rt/<slug>": "^1.0.0"` (never `*` or `dev-main`), and add `"He4rt\\<Pascal>\\Tests\\": "app-modules/<slug>/tests/"` to `autoload-dev`. Then run `composer update he4rt/<slug>`.
3. The label sync from `domain/module-architecture`: a row in `workflow/triage-labels`, and `gh label create "mod:<slug>"`.
4. Add the module to "Modules that exist today" in `domain/module-architecture`, and a row to its type table when it is a new kind.
5. Write `app-modules/<slug>/CONTEXT.md` with the terms from the interview: each term, what it means here, and the words to avoid.
6. Put the module in the gates. The lists are explicit, so a module that is not in them is not checked:
    - a domain module: its `src` and `database` paths in `tests/Arch/DomainIndependenceTest.php`, and its own `tests/Arch/<Pascal>ArchTest.php` like `IdentityArchTest`;
    - a module with `Http/` folders: its slug in `HTTP_MODULES` in `tests/Arch/HttpLayerTest.php`.
7. In `CONTEXT-MAP.md`, set the status to `active`. In `docs/roadmap.md`, set the phase to `started`.
8. Run the checks in `general` (rector, pint, phpstan, the test suite).

Commit each module on its own. The other modules stay `planned` until their phase starts. Then run this skill again: it starts at step 1 and finds the map.

## Wrap up

Tell the user:

- what `PRODUCT.md` now says, and the decisions that are still open;
- the module map and the phases, as a diagram;
- which modules you scaffolded and which ones wait for a later phase.
