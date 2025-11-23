<?php

declare(strict_types=1);

namespace He4rt\Integrations\Twitch\OAuth\Client;

use GuzzleHttp\Client;
use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthStateDTO;
use He4rt\Integrations\Twitch\OAuth\Contracts\TwitchOAuthService;
use He4rt\Integrations\Twitch\OAuth\DTO\TwitchOAuthAccessDTO;
use He4rt\Integrations\Twitch\OAuth\DTO\TwitchOAuthDTO;

final readonly class TwitchOAuthClient implements TwitchOAuthService
{
    public function __construct(private Client $client) {}

    public function redirectUrl(?OAuthStateDTO $state = null): string
    {
        return 'https://id.twitch.tv/oauth2/authorize?'.http_build_query([
            'client_id' => config('services.twitch.client_id'),
            'redirect_uri' => config('services.twitch.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('services.twitch.scopes'),
            'state' => (string) $state,
        ]);
    }

    public function auth(string $code): TwitchOAuthAccessDTO
    {
        $uri = 'https://id.twitch.tv/oauth2/token';
        $response = $this->client->request('POST', $uri, [
            'form_params' => [
                'client_id' => config('services.twitch.client_id'),
                'client_secret' => config('services.twitch.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.twitch.redirect_uri'),
            ],
        ]);

        return TwitchOAuthAccessDTO::make(
            json_decode($response->getBody()->getContents(), true)
        );
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): TwitchOAuthDTO
    {
        $uri = 'https://api.twitch.tv/helix/users';
        $response = $this->client->request('GET', $uri, [
            'headers' => [
                'Client-ID' => config('services.twitch.client_id'),
                'Authorization' => 'Bearer '.$credentials->accessToken,
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);

        return TwitchOAuthDTO::make($credentials, $payload);
    }
}
