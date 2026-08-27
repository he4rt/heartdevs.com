<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ActionType: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case Warn = 'warn';
    case Mute = 'mute';
    case Kick = 'kick';
    case Ban = 'ban';
    case Suspend = 'suspend';
    case ContentRemove = 'content_remove';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.action_type.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Warn => Color::Amber,
            self::Mute => Color::Gray,
            self::Kick => Color::Blue,
            self::Ban => Color::Red,
            self::Suspend => Color::Orange,
            self::ContentRemove => Color::Purple,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Warn => Heroicon::ExclamationTriangle,
            self::Mute => Heroicon::SpeakerXMark,
            self::Kick => Heroicon::ArrowRightEndOnRectangle,
            self::Ban => Heroicon::NoSymbol,
            self::Suspend => Heroicon::Clock,
            self::ContentRemove => Heroicon::Trash,
        };
    }
}
