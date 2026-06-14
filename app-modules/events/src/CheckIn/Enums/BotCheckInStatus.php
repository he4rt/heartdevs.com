<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Enums;

enum BotCheckInStatus: string
{
    case Success = 'success';
    case Error = 'error';
}
