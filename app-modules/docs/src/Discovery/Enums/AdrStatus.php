<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Enums;

use Illuminate\Support\Str;

/**
 * Lifecycle status of an Architecture Decision Record.
 */
enum AdrStatus: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Superseded = 'superseded';
    case Deprecated = 'deprecated';
    case Rejected = 'rejected';

    /**
     * Parse a raw status string from front-matter or the inline `**Status:**`
     * line (e.g. "Accepted (2026-06-08)") into a known status.
     *
     * Falls back to Proposed when the value is unrecognized.
     */
    public static function fromRaw(?string $raw): self
    {
        if ($raw === null) {
            return self::Proposed;
        }

        $token = Str::of($raw)->lower()->trim()->before('(')->before(' ')->trim()->value();

        return self::tryFrom($token) ?? self::Proposed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposto',
            self::Accepted => 'Aceito',
            self::Superseded => 'Substituído',
            self::Deprecated => 'Descontinuado',
            self::Rejected => 'Rejeitado',
        };
    }

    /**
     * Flux badge color.
     */
    public function color(): string
    {
        return match ($this) {
            self::Proposed => 'amber',
            self::Accepted => 'green',
            self::Superseded => 'zinc',
            self::Deprecated => 'orange',
            self::Rejected => 'red',
        };
    }
}
