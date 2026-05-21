<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

final class TwitchOAuthConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $redirectUri,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://id.twitch.tv/oauth2';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
