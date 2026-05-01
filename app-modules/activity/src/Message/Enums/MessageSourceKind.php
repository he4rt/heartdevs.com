<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Enums;

enum MessageSourceKind: string
{
    case User = 'user';
    case Bot = 'bot';
    case Webhook = 'webhook';
    case App = 'app';
}
