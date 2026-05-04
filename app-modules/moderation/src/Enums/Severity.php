<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Severity: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.severity.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Low => Color::Gray,
            self::Medium => Color::Amber,
            self::High => Color::Orange,
            self::Critical => Color::Red,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Low => Heroicon::InformationCircle,
            self::Medium => Heroicon::ExclamationTriangle,
            self::High => Heroicon::Fire,
            self::Critical => Heroicon::ShieldExclamation,
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
