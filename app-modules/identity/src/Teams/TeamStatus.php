<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams;

use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;

enum TeamStatus: string implements HasLabel, HasTone
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __('teams::team_status.'.$this->value.'.label');
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::Active => Tone::Success,
            self::Suspended => Tone::Warning,
            self::Archived => Tone::Danger,
        };
    }
}
