<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\Streams;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetStreams extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $userId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/streams';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'user_id' => $this->userId,
        ];
    }
}
