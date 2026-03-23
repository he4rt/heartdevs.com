<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Subscriber\Contracts;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\IntegrationTwitch\Subscriber\DTO\TwitchSubscriberDTO;

interface TwitchSubscribersService
{
    public function getSubscriptionState(OAuthAccessDTO $dto, string $twitchId, string $channelId): ?TwitchSubscriberDTO;
}
