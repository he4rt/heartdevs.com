---
paths:
  - 'tests/**'
  - 'app-modules/*/tests/**'
  - 'phpunit.xml'
---

# Tests

The suite runs on Pest. Create a test with `php artisan make:test --pest {name}` (`--unit` for a unit test) and run it with `php artisan test --compact` (`--filter=`, `--testsuite=` or `--group=`).

## Suites

`phpunit.xml` (testsuite directories) and `tests/Pest.php` (the `pest()->...->in(...)` bindings) both declare three suites. Each covers the root `tests/` folder and every `app-modules/*/tests/` folder. When you add a suite or a module, change both files.

| Suite | Folders | Binding | Database |
| --- | --- | --- | --- |
| `Unit` | `tests/Unit`, `app-modules/*/tests/Unit` | group `unit` | no |
| `Feature` | `tests/Feature`, `app-modules/*/tests/Feature` | group `feature`, `LazilyRefreshDatabase` | yes |
| `Arch` | `tests/Arch`, `app-modules/*/tests/Arch` | group `arch` | no |

## Authorization and validation tests

- Every form request has feature tests that send the real request: one for each way it allows, and one for each way it refuses (`assertForbidden()`, `assertNotFound()`, `assertSessionHasErrors()`). A refusal test also asserts that nothing changed.

## Architecture tests

- System-wide rules go in `tests/Arch/ArchTest.php`: strict types, no debug calls, the panels do not depend on each other.
- A rule for one module goes in `app-modules/<slug>/tests/Arch/<Module>ArchTest.php`: the domain never uses a presentation module, no `stdClass`, enum contracts scoped to the namespace where they hold. A module-wide `toBeEnums()->toImplement(...)` fails on internal enums, so scope it.
- A structural gate written with `test()` and reflection (`NoLooseArrayCastsTest`, `ModuleTestNamespacesRegisteredTest`) goes in `tests/Arch/`, not in `tests/Unit/`.
- An arch rule must pass when you write it. Check the fact with `rg` or reflection first. If it is not true yet, fix the code so the rule becomes the gate, or do not write the rule.
- `phpunit --list-tests` under-counts `arch()` tests. Trust the count of a `--group=arch` run.
- When you rename a namespace, change every `expect('He4rt\X')` and `not->toUse('He4rt\Y')` that names it in the same edit.

## Module test namespaces

When a module gets a shared test class (a base `TestCase`, a trait, a fixture in `tests/Support/`, a dataset), add its `He4rt\<Module>\Tests\` namespace to the root `composer.json` `autoload-dev.psr-4` in the same change, and keep the entry in the module's own `composer.json` too.

Composer never loads the `autoload-dev` of a dependency, and `internachi/modular` installs every module as a path dependency. A plain `*Test.php` class still runs, because PHPUnit includes it by path, but a shared class fails with `Class not found` without the root entry.

```json
{
    "autoload-dev": {
        "psr-4": {
            "He4rt\\Identity\\Tests\\": "app-modules/identity/tests/",
            "He4rt\\PanelAdmin\\Tests\\": "app-modules/panel-admin/tests/",
            "Tests\\": "tests/"
        }
    }
}
```

- Read the prefix from the module's `composer.json`, sort the entries and run `composer dump-autoload`.
- `tests/Arch/ModuleTestNamespacesRegisteredTest.php` fails when a module with a `tests/` folder has no root entry, and prints the line to add.
