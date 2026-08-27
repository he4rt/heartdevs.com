<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TriggeredBy: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case User = 'user';
    case Admin = 'admin';
    case System = 'system';

    public function getLabel(): string
    {
        return __('events::enums.triggered_by.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::User => Color::Blue,
            self::Admin => Color::Amber,
            self::System => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::User => Heroicon::User,
            self::Admin => Heroicon::ShieldCheck,
            self::System => Heroicon::Cog6Tooth,
        };
    }
}
