<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;

enum WideEventKind: string implements HasLabel, HasTone
{
    case Http = 'http';
    case Job = 'job';
    case Command = 'command';
    case Schedule = 'schedule';
    case Mcp = 'mcp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Http => 'Request',
            self::Job => 'Job',
            self::Command => 'Command',
            self::Schedule => 'Scheduled task',
            self::Mcp => 'MCP tool',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::Http => Tone::Info,
            self::Job => Tone::Primary,
            self::Command => Tone::Neutral,
            self::Schedule => Tone::Neutral,
            self::Mcp => Tone::Warning,
        };
    }

    public function alwaysKept(): bool
    {
        return match ($this) {
            self::Http, self::Job => false,
            self::Command, self::Schedule, self::Mcp => true,
        };
    }
}
