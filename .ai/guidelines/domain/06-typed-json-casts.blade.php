@php
    /** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Typed JSON Casts — Ban the Loose Array Cast

**Priority: HIGH.** Every JSON/jsonb column with a known or semi-structured shape MUST be
cast to a typed value object. A loose `'metadata' => 'array'` is an untyped, unvalidated,
refactor-hostile blind spot: PHPStan collapses to `mixed`, malformed payloads become
silent missing keys, and renaming a key is find-and-pray.

The same rule applies beyond casts: **prefer DTOs/VOs over associative arrays and
`stdClass` anywhere a shape is predictable** — command payloads, event properties,
integration Response Objects (Saloon), service returns.

## Banned casts (mechanically enforced)

Banned inside any model's `casts()`/`$casts`: `'array'`, `'json'`, `'object'`,
`'collection'`, `encrypted:array|collection|object`, `AsArrayObject`, `AsCollection`,
`AsEnumArrayObject`, `AsEnumCollection`, `AsEncryptedArrayObject`,
`AsEncryptedCollection`, and `SchemalessAttributes`.

The gate is `tests/Unit/NoLooseArrayCastsTest.php` — it reflects over every concrete
model (`app/Models` + `app-modules/*/src`), reads the real merged `getCasts()`, and fails
on any banned cast not explicitly allowlisted there. The allowlist is the documented
escape hatch for **genuinely polymorphic** JSON (each entry carries a reason); keep it
trending toward empty.

## The pattern

<code-snippet name="Before / after" lang="php">
// BEFORE — blind
'metadata' => 'array',
$model->metadata['last_projects_sync_at'] ?? null;   // mixed · magic string

// AFTER — typed VO cast (see AsCredentials in the identity module for a live example)
'metadata' => AsIdentityMetadata::class,
$model->metadata->lastSyncAt(Capability::Projects);  // ?CarbonImmutable · verified
</code-snippet>

The VO owns shape, validation and accessors (`fromArray()` / `toArray()` / immutable
`withX()` copies). The cast is only the JSON ↔ VO bridge.

## Rules of thumb

- **Key/format conventions live on ONE owner** (usually the enum:
  `$capability->lastSyncedAtKey()`) — never reverse-parse strings to recover a key.
- **The cast generic is a contract for the setter too**: declare
  `CastsAttributes<TGet, TGet|array<array-key, mixed>>` so legacy array assignment stays
  a reachable, typed branch (`match (true)`).
- `json_decode` returns `array<array-key, mixed>`, never your shape — guard keys or
  iterate `EnumClass::cases()` instead of touching arbitrary keys.
- Genuinely polymorphic/freeform JSON → allowlist it **with a reason** and revisit.
- Never introduce `stdClass` in domain code; defensive layers (e.g. the kernel's
  `CanonicalJson`) may normalize it, but no domain object produces one.
