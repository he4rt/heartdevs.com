@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Migrations & Timezone-Aware Dates

**Priority: HIGH** — These rules are non-negotiable. Every migration MUST be created via Artisan with the correct module flag, and every date/time column MUST use timezone-aware types.

## Creating migrations

Always use `{{ $assist->artisanCommand('make:migration') }}` to create migration files. Never create migration files manually.

This is a modular monorepo (`internachi/modular`). Every migration MUST target its module with the `--module` flag:

@verbatim
<code-snippet name="Creating a migration for a module" lang="bash">
# Always specify the module:
php artisan make:migration create_example_table --module=identity
php artisan make:migration add_expires_at_to_tokens --module=identity --table=tokens

# NEVER create migrations without --module (they end up in the wrong directory):
# BAD:  php artisan make:migration create_example_table
# GOOD: php artisan make:migration create_example_table --module=identity
</code-snippet>
@endverbatim

The `--module` flag places the migration in `app-modules/{module}/database/migrations/`, which is where the module's ServiceProvider loads migrations from.

## Timezone-aware date columns — mandatory

When creating or altering any database column that stores a date, time, or datetime value, you MUST use the timezone-aware (`Tz`) variant. Never use the non-tz variants.

### Required mappings

| NEVER use | ALWAYS use instead |
|-----------|-------------------|
| `$table->timestamp('col')` | `$table->timestampTz('col')` |
| `$table->timestamps()` | `$table->timestampsTz()` |
| `$table->softDeletes()` | `$table->softDeletesTz()` |
| `$table->dateTime('col')` | `$table->dateTimeTz('col')` |
| `$table->nullableTimestamps()` | `$table->timestampsTz()` with `->nullable()` |

### Context

This project uses PostgreSQL with `APP_TIMEZONE=UTC` and `display_timezone=America/Sao_Paulo`. The `timestamptz` type stores absolute UTC timestamps, allowing PostgreSQL to handle timezone conversion correctly. The non-tz `timestamp` type stores naive datetimes that lose timezone context and cause ±3h display bugs.

### Display timezone

When displaying dates to users, always use `config('app.display_timezone')`:

@verbatim
<code-snippet name="Display timezone conversion" lang="php">
// In Blade/Livewire:
$date->timezone(config('app.display_timezone'))->format('d/m/Y H:i')

// In Filament table columns:
TextColumn::make('created_at')
    ->dateTime('d/m/Y H:i')
    ->timezone(config('app.display_timezone'))
</code-snippet>
@endverbatim

### In raw SQL queries

When converting timestamps for display in raw SQL, use `AT TIME ZONE` with the display timezone:

@verbatim
<code-snippet name="SQL timezone conversion" lang="sql">
-- Convert timestamptz to display timezone:
SELECT occurred_at AT TIME ZONE 'America/Sao_Paulo' AS local_time
FROM events;

-- NEVER use double AT TIME ZONE (causes +3h shift):
-- BAD:  occurred_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Sao_Paulo'
-- GOOD: occurred_at AT TIME ZONE 'America/Sao_Paulo'
</code-snippet>
@endverbatim

### Carbon usage

@verbatim
<code-snippet name="UTC timestamp creation" lang="php">
// Correct — uses app timezone (UTC):
now()
Carbon::now()

// For explicit UTC:
now()->utc()

// NEVER hardcode timezone in application logic:
// BAD:  now()->timezone('America/Sao_Paulo')
// GOOD: now()  (app is UTC, display converts later)
</code-snippet>
@endverbatim

### PostgreSQL session timezone

Do NOT set `'timezone' => 'UTC'` in `config/database.php` pgsql connection. This causes double-conversion on `timestamptz` columns. Let PostgreSQL use its server default.

## What triggers this guideline

- `{{ $assist->artisanCommand('make:migration') }}` — always use `--module=<module>`
- Any migration that adds date/time columns — always use `Tz` variants
- Any edit to an existing migration that touches date/time columns
- Creating a model with `{{ $assist->artisanCommand('make:model') }}` — ensure generated migration uses `timestampsTz()`
- Display code showing dates — use `config('app.display_timezone')`
- Raw SQL with timestamp conversion — use single `AT TIME ZONE`

## Verification

Before marking any migration task as done, confirm:

1. Migration was created via `{{ $assist->artisanCommand('make:migration') }}` with `--module=<module>`
2. Migration file lives in `app-modules/{module}/database/migrations/`
3. All new date/time columns use the `Tz` variant
4. No `timestamps()`, `timestamp()`, `softDeletes()`, or `dateTime()` without `Tz`
5. Display code uses `config('app.display_timezone')` for user-facing dates
6. Raw SQL queries use single `AT TIME ZONE` (never double)
