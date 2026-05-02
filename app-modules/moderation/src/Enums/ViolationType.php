<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ViolationType: string implements HasColor, HasIcon, HasLabel
{
    case Spam = 'spam';
    case Toxicity = 'toxicity';
    case Harassment = 'harassment';
    case Nsfw = 'nsfw';
    case Raid = 'raid';
    case Impersonation = 'impersonation';
    case Other = 'other';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.violation_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Spam => 'gray',
            self::Toxicity => 'orange',
            self::Harassment => 'danger',
            self::Nsfw => 'purple',
            self::Raid => 'danger',
            self::Impersonation => 'warning',
            self::Other => 'info',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Spam => Heroicon::EnvelopeOpen,
            self::Toxicity => Heroicon::Fire,
            self::Harassment => Heroicon::HandRaised,
            self::Nsfw => Heroicon::EyeSlash,
            self::Raid => Heroicon::UserGroup,
            self::Impersonation => Heroicon::UserMinus,
            self::Other => Heroicon::QuestionMarkCircle,
        };
    }
}
