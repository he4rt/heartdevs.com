<?php

declare(strict_types=1);

namespace He4rt\Provider\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ProviderEnum: string implements HasColor, HasIcon, HasLabel
{
    case Discord = 'discord';
    case Twitch = 'twitch';

    public function getColor(): array
    {
        return match ($this) {
            self::Discord => Color::Blue,
            self::Twitch => Color::Purple,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Discord => Heroicon::Cloud,
            self::Twitch => Heroicon::Wifi,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Discord => 'Discord',
            self::Twitch => 'Twitch',
        };
    }
}
