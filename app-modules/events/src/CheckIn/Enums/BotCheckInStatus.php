<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Enums;

use App\Enums\Concerns\StringifyEnum;

enum BotCheckInStatus: string
{
    use StringifyEnum;

    case Success = 'success';
    case Error = 'error';
}
