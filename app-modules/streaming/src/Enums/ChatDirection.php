<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasLabel;

enum ChatDirection: string implements HasLabel
{
    case NewestAtBottom = 'newest_bottom';
    case NewestAtTop = 'newest_top';

    public function getLabel(): string
    {
        return match ($this) {
            self::NewestAtBottom => 'Mais novas embaixo',
            self::NewestAtTop => 'Mais novas em cima',
        };
    }
}
