<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Transport;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

final class GitHubApiConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
