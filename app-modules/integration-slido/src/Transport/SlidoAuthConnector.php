<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;

final class SlidoAuthConnector extends Connector
{
    use AlwaysThrowOnErrors;
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 10;

    public function __construct(
        private readonly string $baseUrl,
        public readonly string $clientId,
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }
}
