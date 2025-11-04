<?php

declare(strict_types=1);

namespace App\Providers\Filament;

enum FilamentPanel: string
{
    case Admin = 'admin';
    case Partner = 'partner';
    case User = 'app';
    case Guest = 'guest';

    public function moduleName(string $module): string
    {
        return sprintf('%s-%s', $this->value, $module);
    }
}
