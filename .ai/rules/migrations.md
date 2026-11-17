---
paths:
  - 'database/migrations/**'
  - 'app-modules/*/database/migrations/**'
---

# Migrations

## Migrations are forward-only — no down() method
Do not write a `down()` method in migrations. `make:migration` scaffolds one by default — delete it, leaving only `up()`. This project treats migrations as forward-only: schema is never rolled back with `migrate:rollback`; you re-migrate from a fresh state instead. A `down()` method is dead code that implies a rollback path we do not support. Applies to the conventional Laravel path here and to every module's `database/migrations/`.

When removing a `down()` from an existing migration, also delete any `private`/`protected` helper methods that only `down()` called — once `down()` is gone they are unreachable dead code. Check the class for helpers referenced solely inside the removed method before finishing.

## Create migrations with Artisan and the module flag

Always create migrations with `php artisan make:migration`, never by hand. In this modular monorepo every module migration MUST pass `--module=<module>`, which places the file in `app-modules/<module>/database/migrations/` where the module ServiceProvider loads it — e.g. `make:migration create_x_table --module=identity`. A migration without `--module` lands in the wrong directory.

## Timestamps name the moment

Every date or time column ends in `_at` and names the moment it records: `created_at`, `confirmed_at`, `last_used_at`. Never `_since`, `_on`, `_date`, `_time` or a bare noun.

## Date/time columns are timezone-aware

Every date/time column MUST use the `Tz` variant. PostgreSQL stores `timestamptz` as an absolute UTC instant; the non-tz type stores a naive datetime, loses the timezone and causes ±3h display bugs.

| Never | Always |
|-------|--------|
| `timestamp('col')` | `timestampTz('col')` |
| `timestamps()` | `timestampsTz()` |
| `softDeletes()` | `softDeletesTz()` |
| `dateTime('col')` | `dateTimeTz('col')` |
| `nullableTimestamps()` | `timestampsTz()` with `->nullable()` |
