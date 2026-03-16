<?php

declare(strict_types=1);

namespace He4rt\Integrations\Twitch\Subscriber\Contracts;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;

interface TwitchSubscribersService
{
    public function getSubscriptionState(OAuthAccessDTO $dto, string $twitchId, string $channelId);
}
