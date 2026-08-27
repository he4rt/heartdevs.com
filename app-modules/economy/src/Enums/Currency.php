<?php

declare(strict_types=1);

namespace He4rt\Economy\Enums;

use App\Enums\Concerns\StringifyEnum;

enum Currency: string
{
    use StringifyEnum;

    case Coin = 'coin';
    case Cash = 'cash';
}
