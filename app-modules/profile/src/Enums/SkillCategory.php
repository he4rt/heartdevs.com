<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SkillCategory: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case Language = 'language';
    case Framework = 'framework';
    case Database = 'database';
    case Tool = 'tool';
    case Soft = 'soft';

    public function getLabel(): string
    {
        return __('profile::enums.skill_category.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Language => Color::Blue,
            self::Framework => Color::Purple,
            self::Database => Color::Emerald,
            self::Tool => Color::Amber,
            self::Soft => Color::Rose,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Language => Heroicon::CodeBracket,
            self::Framework => Heroicon::CubeTransparent,
            self::Database => Heroicon::CircleStack,
            self::Tool => Heroicon::WrenchScrewdriver,
            self::Soft => Heroicon::UserGroup,
        };
    }
}
