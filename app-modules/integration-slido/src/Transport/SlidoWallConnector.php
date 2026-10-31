<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

final class SlidoWallConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function __construct(
        private readonly string $cluster,
        private readonly ?string $accessToken = null,
    ) {
        if ($this->accessToken !== null) {
            $this->authenticate(new TokenAuthenticator($this->accessToken));
        }
    }

    public function resolveBaseUrl(): string
    {
        return sprintf('%s/%s/api/v0.5', mb_rtrim(config()->string('integration-slido.wall_url'), '/'), $this->cluster);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }
}
