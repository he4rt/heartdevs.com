<?php

declare(strict_types=1);

namespace He4rt\Integrations\Discord\OAuth;

use He4rt\Authentication\Contracts\OAuthClientContract;
use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;
use Illuminate\Support\Facades\Http;

class DiscordOAuthClient implements OAuthClientContract
{
    public function redirectUrl(): string
    {
        return sprintf(
            'https://discord.com/oauth2/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=%s',
            config('services.discord.client_id'),
            config('services.discord.redirect_uri'),
            config('services.discord.scopes'),
        );
    }

    public function auth(string $code): OAuthAccessDTO
    {
        $request = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.discord.redirect_uri'),
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
        ]);

        return DiscordOAuthAccessDTO::make($request->json());
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO
    {
        $response = Http::withToken($credentials->accessToken)
            ->get('https://discord.com/api/v10/users/@me');

        return DiscordOAuthUser::make($credentials, $response->json());
    }
}
