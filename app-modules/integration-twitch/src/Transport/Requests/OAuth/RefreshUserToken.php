<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\OAuth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

final class RefreshUserToken extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}
