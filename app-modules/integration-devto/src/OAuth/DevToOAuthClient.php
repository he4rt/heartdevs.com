<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\OAuth;

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use Illuminate\Support\Facades\Http;

class DevToOAuthClient implements OAuthClientContract
{
    public function redirectUrl(?OAuthStateDTO $state = null): string
    {
        return 'https://dev.to/oauth/authorize?'.http_build_query([
            'client_id' => config('services.devto.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.devto.redirect_uri'),
            'scope' => config('services.devto.scopes'),
            'state' => (string) $state,
        ]);
    }

    public function auth(string $code): OAuthAccessDTO
    {
        $response = Http::asForm()->post('https://dev.to/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.devto.redirect_uri'),
            'client_id' => config('services.devto.client_id'),
            'client_secret' => config('services.devto.client_secret'),
        ]);

        return DevToOAuthAccessDTO::make($response->json());
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO
    {
        $response = Http::withToken($credentials->accessToken)
            ->get('https://dev.to/api/users/me');

        return DevToOAuthUser::make($credentials, $response->json());
    }
}
