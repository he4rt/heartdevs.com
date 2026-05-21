<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

enum StartAvailability: string
{
    case Immediate = 'immediate';
    case OneWeek = '1_week';
    case TwoWeeks = '2_weeks';
    case ThreeWeeks = '3_weeks';
    case OneMonth = '1_month';
    case TwoMonths = '2_months';
    case Negotiable = 'negotiable';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Imediato',
            self::OneWeek => '1 semana',
            self::TwoWeeks => '2 semanas',
            self::ThreeWeeks => '3 semanas',
            self::OneMonth => '1 mês',
            self::TwoMonths => '2 meses',
            self::Negotiable => 'Negociável',
        };
    }
}
