<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum OverlayScene: string implements HasDescription, HasLabel
{
    case Coworking = 'coworking';
    case StartingSoon = 'starting';
    case Alerts = 'alerts';
    case Chat = 'chat';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coworking => 'Coworking',
            self::StartingSoon => 'A live vai começar',
            self::Alerts => 'Alertas',
            self::Chat => 'Chat',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Coworking => 'Moldura completa: câmera, chat, barra de nível e alertas no rodapé.',
            self::StartingSoon => 'Cena de abertura com contagem regressiva.',
            self::Alerts => 'Só os alertas, com fundo transparente, por cima de qualquer cena.',
            self::Chat => 'Só o chat, com fundo transparente.',
        };
    }
}
