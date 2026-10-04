<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationTwitch\OAuth\DTO\TwitchOAuthAccessDTO;
use He4rt\IntegrationTwitch\OAuth\DTO\TwitchOAuthDTO;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationTwitch\Transport\Requests\Users\GetCurrentUser;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Auth;

final readonly class TwitchOAuthClient implements OAuthClientContract
{
    public function __construct(
        private TwitchOAuthConnector $oauthConnector,
        private TwitchHelixConnector $helixConnector,
    ) {}

    public function redirectUrl(?OAuthStateDTO $state = null): string
    {
        $scopes = TwitchScopes::requestedFor($state->panel ?? 'app', Auth::user());

        $callbackUrl = $this->callbackUrl();

        return 'https://id.twitch.tv/oauth2/authorize?'.http_build_query([
            'client_id' => $this->oauthConnector->clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => (string) $state,
        ]);
    }

    public function auth(string $code): TwitchOAuthAccessDTO
    {
        $response = $this->oauthConnector->send(new ExchangeCodeForToken(
            code: $code,
            clientId: $this->oauthConnector->clientId,
            clientSecret: $this->oauthConnector->getClientSecret(),
            redirectUri: $this->callbackUrl(),
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $tokenExchangeFailed = !isset($payload['access_token']);

        if ($tokenExchangeFailed) {
            throw OAuthFlowException::tokenExchangeFailed('twitch', $payload['message'] ?? $payload['error'] ?? 'unknown');
        }

        return TwitchOAuthAccessDTO::make($payload);
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): TwitchOAuthDTO
    {
        $response = $this->helixConnector->send(new GetCurrentUser(
            accessToken: $credentials->accessToken,
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return TwitchOAuthDTO::make($credentials, $payload);
    }

    private function callbackUrl(): string
    {
        return mb_rtrim(config('app.url'), '/').'/auth/oauth/twitch';
    }
}
