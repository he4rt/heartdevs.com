<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasLabel;

enum ChatAlignment: string implements HasLabel
{
    case Left = 'left';
    case Right = 'right';

    public function getLabel(): string
    {
        return match ($this) {
            self::Left => 'Esquerda',
            self::Right => 'Direita',
        };
    }
}
