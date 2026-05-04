<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CaseSource: string implements HasColor, HasIcon, HasLabel
{
    case UserReport = 'user_report';
    case AutoDetect = 'auto_detect';
    case RuleMatch = 'rule_match';
    case ManualFlag = 'manual_flag';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.case_source.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::UserReport => Color::Blue,
            self::AutoDetect => Color::Purple,
            self::RuleMatch => Color::Amber,
            self::ManualFlag => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::UserReport => Heroicon::Flag,
            self::AutoDetect => Heroicon::Bolt,
            self::RuleMatch => Heroicon::ShieldExclamation,
            self::ManualFlag => Heroicon::HandRaised,
        };
    }
}
