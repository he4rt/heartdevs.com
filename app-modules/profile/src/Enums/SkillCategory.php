<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

enum SkillCategory: string
{
    case LanguagesFrameworks = 'languages_frameworks';
    case InfraDatabases = 'infra_databases';
    case SoftSkillsTools = 'softskills_tools';
    case Idiomas = 'idiomas';

    public function label(): string
    {
        return match ($this) {
            self::LanguagesFrameworks => 'Linguagens & Frameworks',
            self::InfraDatabases => 'Infraestrutura & Databases',
            self::SoftSkillsTools => 'Soft Skills & Ferramentas',
            self::Idiomas => 'Idiomas',
        };
    }

    public function limit(): int
    {
        return match ($this) {
            self::LanguagesFrameworks => 6,
            self::InfraDatabases => 6,
            self::SoftSkillsTools => 15,
            self::Idiomas => PHP_INT_MAX,
        };
    }
}
