<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ActivityStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case AutoApproved = 'auto_approved';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AutoApproved => 'Auto Approved',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Yellow,
            self::AutoApproved => Color::Blue,
            self::InReview => Color::Orange,
            self::Approved => Color::Green,
            self::Rejected => Color::Red,
        };
    }
}
