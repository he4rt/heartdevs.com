<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Platform: string implements HasColor, HasIcon, HasLabel
{
    case Discord = 'discord';
    case Twitch = 'twitch';
    case GitHub = 'github';
    case Twitter = 'twitter';
    case Web = 'web';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.platform.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Discord => 'purple',
            self::Twitch => 'violet',
            self::GitHub => 'gray',
            self::Twitter => 'info',
            self::Web => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Discord => Heroicon::ChatBubbleLeftRight,
            self::Twitch => Heroicon::Signal,
            self::GitHub => Heroicon::CodeBracket,
            self::Twitter => Heroicon::Megaphone,
            self::Web => Heroicon::GlobeAlt,
        };
    }
}
