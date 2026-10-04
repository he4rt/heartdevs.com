<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum OverlayScene: string implements HasColor, HasDescription, HasLabel
{
    case Coworking = 'coworking';
    case StartingSoon = 'starting';
    case Voice = 'voice';

    public function getLabel(): string
    {
        return match ($this) {
            self::Coworking => 'Coworking',
            self::StartingSoon => 'A live vai começar',
            self::Voice => 'Sala de voz',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Coworking => 'Moldura completa: câmera, chat, barra de nível e alertas no rodapé.',
            self::StartingSoon => 'Cena de abertura com contagem regressiva.',
            self::Voice => 'Quem está na call do Discord, com fundo transparente, por cima de qualquer cena.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Coworking => 'primary',
            self::StartingSoon => 'info',
            self::Voice => 'success',
        };
    }
}
