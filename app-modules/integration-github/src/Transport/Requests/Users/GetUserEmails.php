<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Transport\Requests\Users;

use Saloon\Enums\Method;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;

final class GetUserEmails extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $accessToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/user/emails';
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->accessToken);
    }
}
