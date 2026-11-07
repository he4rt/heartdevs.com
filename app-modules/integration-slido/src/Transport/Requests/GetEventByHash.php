<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetEventByHash extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $eventHash) {}

    public function resolveEndpoint(): string
    {
        return '/app/events';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return ['hash' => $this->eventHash];
    }
}
