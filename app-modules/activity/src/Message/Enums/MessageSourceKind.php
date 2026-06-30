<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Enums;

use App\Enums\Concerns\StringifyEnum;

enum MessageSourceKind: string
{
    use StringifyEnum;

    case User = 'user';
    case Bot = 'bot';
    case Webhook = 'webhook';
    case App = 'app';
}
