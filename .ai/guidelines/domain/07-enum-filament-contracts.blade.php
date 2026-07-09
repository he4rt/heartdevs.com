@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp

# Enums — Implement the Filament Contracts Immediately

**Priority: HIGH.** Every time you create a **new** backed enum, implement the Filament
"enum trick" contracts in the **same change** — never as a follow-up pass. Retrofitting
later means touching every enum (and every call site that assumed a bare `->value`) twice.

Domain enums implementing Filament contracts is the established precedent here (the
moderation enums — `CaseStatus`, `Severity`, `AppealStatus`, `ModerationType`, … — all do
it). The panel leans on these contracts for badges, table/infolist columns, `SelectFilter`
options, select descriptions and state-gated UI.

## Mandatory contracts

| Contract | Method | Always? |
|----------|--------|---------|
| `Filament\Support\Contracts\HasLabel` | `getLabel(): string` | Yes |
| `Filament\Support\Contracts\HasColor` | `getColor()` | Yes |
| `Filament\Support\Contracts\HasDescription` | `getDescription(): ?string` | Yes |
| `Filament\Support\Contracts\HasIcon` | `getIcon()` | Only when an icon is genuinely meaningful |

Every getter is a `match ($this)` over **all** cases — no `default` arm, so adding a case
is a compile-time reminder to fill in every trick.

## HasColor — ordered enums ramp light → red

When the enum encodes an **ordered scale** (access level, seniority, severity, risk, SLA
health), `getColor()` MUST form a monotonic light→red heat ramp: the least/lowest case gets
the lightest color, the most/highest gets `danger` (red). The six semantic strings
(`gray`, `info`, `primary`, `success`, `warning`, `danger`) cover short scales; for a longer
monotonic gradient use the `Filament\Support\Colors\Color` palette.

For an **unordered** enum (e.g. document kinds), pick distinct semantic colors per case —
the ramp rule does not apply.

## The pattern

<code-snippet name="New enum with the full contract set" lang="php">
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AccessTier: string implements HasColor, HasDescription, HasIcon, HasLabel
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

    // Ordered scale → light (no access) ramps to red (apex authority).
    public function getColor(): string|array
    {
        return match ($this) {
            self::None => 'gray',
            self::Analyst => Color::Blue,
            self::Admin => Color::Red,
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::None => 'Notification audience — no panel access',
            self::Analyst => 'Day-to-day operator: triage, review, moderation',
            self::Admin => 'Final authority: configures the panel, decides cases',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::None => Heroicon::OutlinedNoSymbol,
            self::Analyst => Heroicon::OutlinedMagnifyingGlass,
            self::Admin => Heroicon::OutlinedScale,
        };
    }
}
</code-snippet>

## Verification

Before marking an enum task done, confirm:

1. The enum `implements HasColor, HasDescription, HasLabel` (and `HasIcon` when meaningful).
2. Every getter `match`es **all** cases with no `default`.
3. Ordered enums ramp light → `danger`; the apex case is red.
4. `{{ $assist->binCommand('pint') }}` and PHPStan pass.
