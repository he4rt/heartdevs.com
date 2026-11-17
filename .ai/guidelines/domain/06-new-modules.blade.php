@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# New Modules

A module is expensive: it has a package, a label, a glossary, gates and an owner. Create one only when the work cannot live in a module that exists.

## When this applies

- Before you create a folder in `app-modules/`, or run `php artisan make:module`.
- When a request names a product area that no module owns: "an e-commerce", "billing", "a marketplace", "notifications".

Then activate the `new-module` skill. Never scaffold a module straight from a request.

## Size the work first

One request can be one feature or many modules. Map the whole request before you write code. "An e-commerce" is a catalog, a cart, orders, payments and shipping, not one module.

Put each capability on the lowest rung that fits. Go up a rung only when the rung below does not fit, and write down why.

| Rung | Where it goes | When |
| --- | --- | --- |
| 1 | A feature in a panel | New screens over records that a module already owns. |
| 2 | A sub-domain of a module that exists (`identity/Teams/Invites`) | New terms and tables, but the records belong to that module, and its glossary covers the words. |
| 3 | A new domain module | Its own glossary (a word means something else here), its own lifecycle, records that no module owns, and it changes for other reasons than its neighbours. |
| 4 | A new `integration-*` module | It talks to an external API: transport, OAuth, sync. |

A new `panel-*` module is a new audience with its own URL root. It is rare, and it never holds domain logic.

## Product context

- `PRODUCT.md` at the root holds the product truth: who uses it, what it makes possible, what must not change. It follows the impeccable product schema. It names no modules, tables or classes.
- `CONTEXT-MAP.md` at the root lists every module, planned or real: its type, its status, the terms it owns and what it depends on.
- `docs/roadmap.md` splits the modules into phases. Each phase has a goal that a user can see.
- Read the three before you size anything. `PRODUCT.md` describes the He4rt Hub today. When `CONTEXT-MAP.md` or `docs/roadmap.md` is missing, the `new-module` skill writes it through an interview, before the next module.

## Scaffold only the current phase

- After the user approves the map, scaffold the modules of the first open phase only. A planned module stays a row in `CONTEXT-MAP.md` until its phase starts.
- A new module follows the checklist in the `new-module` skill: the `^1.0.0` constraint, the label sync, its `CONTEXT.md` and its place in the arch tests.
