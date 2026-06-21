<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Platform: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

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

    public function getColor(): array
    {
        return match ($this) {
            self::Discord => Color::Purple,
            self::Twitch => Color::Violet,
            self::GitHub => Color::Gray,
            self::Twitter => Color::Blue,
            self::Web => Color::Green,
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
