@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Model PHPDoc Sync — Mandatory on Schema Changes

**Priority: HIGH** — This rule is non-negotiable. Every schema change MUST update the corresponding model PHPDoc.

## Rule

When you **add, remove, rename, or change the type** of any database column (via migration, manual SQL, or schema dump), the `@property` PHPDoc block on the affected Model class **MUST be updated in the same commit**.

## What triggers this rule

- `{{ $assist->artisanCommand('make:migration') }}` that adds/removes/alters columns
- Any edit to an existing migration file
- Any raw SQL that changes table structure
- Renaming a column
- Changing a column type (e.g., `string` → `text`, `timestamp` → `timestampTz`)
- Adding/removing nullable
- Adding/removing a default value that changes the PHPDoc type (e.g., nullable → non-nullable)

## PHPDoc format

@verbatim
<code-snippet name="Model PHPDoc block" lang="php">
/**
 * @property string $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property Carbon|null $starts_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'example_table')]
final class Example extends Model
</code-snippet>
@endverbatim

## Type mapping

| Column type | PHPDoc type |
|-------------|-------------|
| `uuid` | `string` |
| `string`, `text` | `string` |
| `integer`, `bigInteger` | `int` |
| `boolean` | `bool` |
| `timestamp`, `datetime`, `timestampTz` | `Carbon\|null` |
| `json`, `jsonb` | `array<string, mixed>\|null` |
| `decimal`, `float` | `float` |
| `enum` (backed) | `EnumClass` |

- Add `|null` when the column is nullable.
- Use the cast type for enums and custom casts, not the raw DB type.
- `created_at` and `updated_at` are always `Carbon|null`.

## Verification

Before marking a migration task as done, confirm:

1. The model file has a `/** @property ... */` block above the class
2. Every column in the table has a corresponding `@property` line
3. Types match the column definition and any explicit `casts()`
4. PHPStan passes (`{{ $assist->binCommand('phpstan analyse') }}`)
