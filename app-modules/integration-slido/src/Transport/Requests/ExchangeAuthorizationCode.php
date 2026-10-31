<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

final class ExchangeAuthorizationCode extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $clientId,
        private readonly string $code,
        private readonly string $redirectUri,
        private readonly string $codeVerifier,
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
        return [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'code' => $this->code,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $this->codeVerifier,
        ];
    }
}
