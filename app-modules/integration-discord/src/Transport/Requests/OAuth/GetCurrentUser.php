<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\OAuth;

use Saloon\Enums\Method;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;

final class GetCurrentUser extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $accessToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users/@me';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->accessToken);
    }
}
