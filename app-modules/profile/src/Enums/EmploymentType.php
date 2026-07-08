<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EmploymentType: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case SalariedEmployee = 'employee';
    case IndependentContractor = 'contractor';
    case Freelancer = 'freelancer';

    public function getLabel(): string
    {
        return __('profile::enums.employment_type.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::SalariedEmployee => Color::Blue,
            self::IndependentContractor => Color::Purple,
            self::Freelancer => Color::Amber,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SalariedEmployee => Heroicon::Briefcase,
            self::IndependentContractor => Heroicon::BuildingOffice,
            self::Freelancer => Heroicon::Bolt,
        };
    }
}
