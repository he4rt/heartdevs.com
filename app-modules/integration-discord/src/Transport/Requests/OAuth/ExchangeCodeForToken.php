<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\OAuth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

final class ExchangeCodeForToken extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $code,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly ?string $redirectUri = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/oauth2/token';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }
}
