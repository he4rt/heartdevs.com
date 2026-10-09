<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\OAuth;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ValidateToken extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $accessToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/validate';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'OAuth '.$this->accessToken,
        ];
    }
}
