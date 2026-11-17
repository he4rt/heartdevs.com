# General Guidelines

This repo is the He4rt Hub, the platform of the He4rt Developers community. `PRODUCT.md` holds the product truth. The rules here are about engineering.

- Every layer has a rule file in `.ai/rules/`. Read the ones whose globs match the files you touch before you write code. The Boost "Project Rules" section says how.
- Do not write browser tests (`tests/Browser`, the Pest browser plugin, Playwright). Cover behavior with Feature and Unit tests.

## Commit attribution

- Never add an AI tool as a co-author: no `Co-authored-by` trailer that names Claude, Amp, Copilot or any other assistant.
- Never put their names anywhere in commit attribution: not as author or committer, and not in a trailer or footer such as "Generated with Claude Code". Commits and pull requests belong to the human contributors.

## Minimal code

- Write the least code that does the job: no private helpers, cache layers or defensive checks that the feature does not need. If you can inline a method or remove a branch without losing behavior, do it.
- Prefer Laravel collections and the framework's typed accessors to hand-written arrays: `collect(...)->map(...)` instead of a loop that builds an array, `->string()` / `->integer()` / `->boolean()` instead of `is_string()` checks and `(int)` casts.
- Never wrap a collection in `array_values()`. End it with `->values()->all()` and type the result `array<int, T>`, not `list<T>`. `.ai/rules/laravel-conventions.md` has the rest of the Laravel conventions.
- For a large or repeated array shape, define a `@phpstan-type` on the class that owns the data, and use `@phpstan-import-type` in other classes. Keep a small, one-off shape inline.

## Required verification

- Before you report that a change is done, run the checks for what it touches:
    - PHP: `vendor/bin/rector process`, `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --memory-limit=1G`.
    - Frontend: `bun run typecheck` and `bun run build`.
    - Tests: the affected tests first, then the full suite (`php artisan test --compact`).
- Fix the failures that the change causes. Never lower a threshold, add a baseline entry or skip a test only to make a check pass.
- Report a failure that was there before the change, or a check that could not run, as exactly that. A check that failed or did not run is not a pass.
