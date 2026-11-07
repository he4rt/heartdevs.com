<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Transport\Requests;

use GuzzleHttp\RequestOptions;
use Saloon\Enums\Method;
use Saloon\Http\Request;

final class AuthorizeParticipant extends Request
{
    public const string SCOPE = 'email openid offline_access profile api:event:participant';

    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $eventUuid,
        private readonly string $clientId,
        private readonly string $redirectUri,
        private readonly string $codeChallenge,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/the-auth/events/%s/authorize/default', rawurlencode($this->eventUuid));
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => self::SCOPE,
            'state' => 'he4rt',
            'code_challenge' => $this->codeChallenge,
            'code_challenge_method' => 'S256',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [RequestOptions::ALLOW_REDIRECTS => ['max' => 3]];
    }
}
