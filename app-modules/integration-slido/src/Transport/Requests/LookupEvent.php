<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class LookupEvent extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $eventHash) {}

    public function resolveEndpoint(): string
    {
        return '/lookup/event/hash/'.rawurlencode($this->eventHash);
    }
}
