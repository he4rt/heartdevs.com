<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SubTier: string implements HasColor, HasDescription, HasLabel
{
    case Tier1 = '1000';
    case Tier2 = '2000';
    case Tier3 = '3000';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tier1 => 'Tier 1',
            self::Tier2 => 'Tier 2',
            self::Tier3 => 'Tier 3',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Tier1 => 'Assinatura de entrada.',
            self::Tier2 => 'Assinatura intermediária.',
            self::Tier3 => 'Assinatura mais alta.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Tier1 => 'gray',
            self::Tier2 => 'warning',
            self::Tier3 => 'danger',
        };
    }
}
