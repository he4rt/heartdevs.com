<?php

declare(strict_types=1);

namespace App\Enums;

enum ToastType: string
{
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
