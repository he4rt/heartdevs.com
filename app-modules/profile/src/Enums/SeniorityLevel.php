<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

enum SeniorityLevel: string
{
    case Junior = 'junior';
    case Pleno = 'pleno';
    case Senior = 'senior';
    case Specialist = 'specialist';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Júnior',
            self::Pleno => 'Pleno',
            self::Senior => 'Sênior',
            self::Specialist => 'Especialista',
            self::Lead => 'Lead',
        };
    }
}
