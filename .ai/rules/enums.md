---
paths:
  - 'app/Enums/**'
  - 'app-modules/*/src/**/*.php'
---

# Enums

A backed enum that a user sees implements the display contracts of `App\Contracts\Enums` in the same change that creates it. A later pass means changing every enum and every call site that read a bare `->value`.

| Contract | Method | When |
| --- | --- | --- |
| `HasLabel` | `getLabel(): string` | Always |
| `HasTone` | `getTone(): Tone` | Always |
| `HasDescription` | `getDescription(): string` | When the label alone does not explain the case |

- Every getter is a `match ($this)` over all cases with no `default` arm, so a new case breaks until every getter has it.
- `App\Enums\Tone` (`Neutral`, `Primary`, `Info`, `Success`, `Warning`, `Danger`) says what a case means. Each panel maps a tone to its colors (`<ToneBadge tone={...}>`), so an enum never names a color.
- The tones of an ordered scale (access level, severity, risk) go from `Neutral` for the lowest case to `Danger` for the highest. For an unordered enum, pick the tone that matches each case.
- The panels read the contracts through `App\Data\Support\EnumOptionData::fromEnum()` for badges, filters and select descriptions.
- After you add or change an enum, run `php artisan typescript:transform`.

```php
enum AccessTier: string implements HasDescription, HasLabel, HasTone
{
    case None = 'none';
    case Analyst = 'analyst';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'No access',
            self::Analyst => 'Analyst',
            self::Admin => 'Administration',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::None => Tone::Neutral,
            self::Analyst => Tone::Info,
            self::Admin => Tone::Danger,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => 'No panel access',
            self::Analyst => 'Reads and acts on records',
            self::Admin => 'Manages users, roles and settings',
        };
    }
}
```
