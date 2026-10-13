<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum VoiceLayout: string implements HasColor, HasDescription, HasLabel
{
    case Column = 'col';
    case Row = 'row';

    public function getLabel(): string
    {
        return match ($this) {
            self::Column => 'Coluna',
            self::Row => 'Linha',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Column => 'Uma pessoa embaixo da outra, para a lateral da tela.',
            self::Row => 'Uma pessoa ao lado da outra, para o rodapé da tela.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Column => 'primary',
            self::Row => 'info',
        };
    }
}
