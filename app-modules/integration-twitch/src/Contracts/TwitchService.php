<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Contracts;

use He4rt\IntegrationTwitch\OAuth\Contracts\TwitchOAuthService;
use He4rt\IntegrationTwitch\Subscriber\Contracts\TwitchSubscribersService;

interface TwitchService
{
    public function oauth(): TwitchOAuthService;

    public function subscribers(): TwitchSubscribersService;
}
