<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetEvent extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $eventUuid) {}

    public function resolveEndpoint(): string
    {
        return '/events/'.rawurlencode($this->eventUuid);
    }
}
