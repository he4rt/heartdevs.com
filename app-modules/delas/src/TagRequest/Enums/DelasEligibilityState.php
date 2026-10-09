<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * A situação de uma pessoa em relação à tag, como o perfil mostra. Nunca é
 * persistida: `DelasEligibility` deriva das solicitações e dos bloqueios.
 */
enum DelasEligibilityState: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case CanRequest = 'can_request';
    case Pending = 'pending';
    case Member = 'member';
    case Cooldown = 'cooldown';
    case Blocked = 'blocked';
    case DiscordRequired = 'discord_required';

    public function getLabel(): string
    {
        return __('delas::enums.eligibility.'.$this->value.'.label');
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CanRequest => 'primary',
            self::Pending => 'warning',
            self::Member => 'success',
            self::Cooldown, self::Blocked, self::DiscordRequired => 'gray',
        };
    }

    public function getDescription(): string
    {
        return __('delas::enums.eligibility.'.$this->value.'.description');
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::CanRequest => Heroicon::OutlinedHeart,
            self::Pending => Heroicon::OutlinedClock,
            self::Member => Heroicon::OutlinedCheckCircle,
            self::Cooldown => Heroicon::OutlinedClock,
            self::Blocked => Heroicon::OutlinedInformationCircle,
            self::DiscordRequired => Heroicon::OutlinedLink,
        };
    }
}
