<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum StreamerStatus: string implements HasColor, HasDescription, HasLabel
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Disabled => 'Desativado',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Tem a role de streamer: overlays e alertas funcionam.',
            self::Disabled => 'Perdeu a role de streamer: overlays param e nenhum dado é apagado.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Disabled => 'gray',
        };
    }
}
