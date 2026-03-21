<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IdentityType: string implements HasColor, HasLabel
{
    case External = 'external';

    public function getLabel(): string
    {
        return match ($this) {
            self::External => 'External Platform',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::External => 'primary',
        };
    }
}
