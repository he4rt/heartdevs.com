<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Client;

use GuzzleHttp\Client;
use He4rt\IntegrationTwitch\Contracts\TwitchService;
use He4rt\IntegrationTwitch\OAuth\Client\TwitchOAuthClient;
use He4rt\IntegrationTwitch\OAuth\Contracts\TwitchOAuthService;
use He4rt\IntegrationTwitch\Subscriber\Client\TwitchSubscribersClient;
use He4rt\IntegrationTwitch\Subscriber\Contracts\TwitchSubscribersService;

final readonly class TwitchBaseClient implements TwitchService
{
    public function __construct(private Client $client) {}

    public function oauth(): TwitchOAuthService
    {
        return new TwitchOAuthClient($this->client);
    }

    public function subscribers(): TwitchSubscribersService
    {
        return new TwitchSubscribersClient($this->client);
    }
}
