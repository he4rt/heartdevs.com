<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\Users;

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
        return '/users';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->accessToken);
    }
}
