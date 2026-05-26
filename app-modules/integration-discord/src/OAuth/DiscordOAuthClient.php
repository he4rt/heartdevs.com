<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\OAuth;

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;

class DiscordOAuthClient implements OAuthClientContract
{
    public function __construct(
        private readonly DiscordOAuthConnector $connector,
    ) {}

    public function redirectUrl(?OAuthStateDTO $state = null): string
    {
        return 'https://discord.com/oauth2/authorize?'.http_build_query([
            'client_id' => $this->connector->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->callbackUrl(),
            'scope' => config('services.discord.scopes'),
            'state' => (string) $state,
        ]);
    }

    public function auth(string $code): OAuthAccessDTO
    {
        $response = $this->connector->send(new ExchangeCodeForToken(
            code: $code,
            clientId: $this->connector->clientId,
            clientSecret: $this->connector->clientSecret,
            redirectUri: $this->callbackUrl(),
        ));

        $payload = $response->json();
        $tokenExchangeFailed = !isset($payload['access_token']);

        if ($tokenExchangeFailed) {
            throw OAuthFlowException::tokenExchangeFailed('discord', $payload['error'] ?? 'unknown');
        }

        return DiscordOAuthAccessDTO::make($payload);
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO
    {
        $response = $this->connector->send(new GetCurrentUser(
            accessToken: $credentials->accessToken,
        ));

        return DiscordOAuthUser::make($credentials, $response->json());
    }

    private function callbackUrl(): string
    {
        return mb_rtrim(config('app.url'), '/').'/auth/oauth/discord';
    }
}
