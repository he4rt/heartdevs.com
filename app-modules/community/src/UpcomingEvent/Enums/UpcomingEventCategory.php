<?php

declare(strict_types=1);

namespace He4rt\Community\UpcomingEvent\Enums;

use Filament\Support\Contracts\HasLabel;

enum UpcomingEventCategory: string implements HasLabel
{
    case ReuniaoSemanal = 'reuniao_semanal';

    case Aula = 'aula';

    case AulaIngles = 'aula_ingles';

    case Onboarding = 'onboarding';

    case Networking = 'networking';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReuniaoSemanal => 'Reunião Semanal',
            self::Aula => 'Aula Livre',
            self::AulaIngles => 'Aula de Inglês',
            self::Onboarding => 'Onboarding',
            self::Networking => 'Networking',
        };
    }
}
