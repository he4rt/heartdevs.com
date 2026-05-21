<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

final class TwitchHelixConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function __construct(
        private readonly string $appToken,
        private readonly string $clientId,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.twitch.tv/helix';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->appToken);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Client-Id' => $this->clientId,
        ];
    }
}
