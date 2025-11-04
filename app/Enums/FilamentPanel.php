<?php

declare(strict_types=1);

namespace App\Enums;

enum FilamentPanel: string
{
    case Admin = 'admin';
    case Partner = 'partner';
    case User = 'user';
    case Guest = 'guest';
}
