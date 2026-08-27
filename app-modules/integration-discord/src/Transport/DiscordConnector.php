<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

final class DiscordConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function __construct(
        private readonly string $botToken,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://discord.com/api/v10';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->botToken, 'Bot');
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
