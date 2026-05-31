<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Enums;

enum OAuthIntent: string
{
    case Login = 'login';
    case Link = 'link';
}
