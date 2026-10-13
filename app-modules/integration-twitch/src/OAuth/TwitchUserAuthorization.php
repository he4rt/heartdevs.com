<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\RefreshUserToken;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ValidateToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

/**
 * User tokens expire after a few hours and nothing else refreshes them, so an expired token is
 * refreshed before the authorization counts as refused.
 */
final readonly class TwitchUserAuthorization
{
    private const int CACHE_SECONDS = 600;

    public function __construct(
        private TwitchOAuthConnector $connector,
    ) {}

    /**
     * @return array<int, string>|null null when Twitch refuses the authorization
     *
     * @throws TwitchUnreachable
     */
    public function grantedScopes(ExternalIdentity $identity): ?array
    {
        $cached = Cache::get($this->cacheKey($identity));

        if (is_array($cached)) {
            return $cached['valid'] === true ? $cached['scopes'] : null;
        }

        $scopes = $this->freshGrantedScopes($identity);
        Cache::put($this->cacheKey($identity), ['valid' => $scopes !== null, 'scopes' => $scopes ?? []], self::CACHE_SECONDS);

        return $scopes;
    }

    public function forget(ExternalIdentity $identity): void
    {
        Cache::forget($this->cacheKey($identity));
    }

    /**
     * @return array<int, string>|null
     *
     * @throws TwitchUnreachable
     */
    private function freshGrantedScopes(ExternalIdentity $identity): ?array
    {
        $accessToken = $identity->credentials->getAccessToken();

        if (is_string($accessToken) && $accessToken !== '') {
            $validation = $this->send(new ValidateToken($accessToken));

            if ($validation->successful()) {
                return $this->scopesIn($validation->json('scopes'));
            }

            if ($validation->status() !== HttpStatus::HTTP_UNAUTHORIZED) {
                throw TwitchUnreachable::while('validating the user token', $validation->toException());
            }
        }

        return $this->refresh($identity);
    }

    /**
     * @return array<int, string>|null
     *
     * @throws TwitchUnreachable
     */
    private function refresh(ExternalIdentity $identity): ?array
    {
        $refreshToken = $identity->credentials->getRefreshToken();

        if (!is_string($refreshToken) || $refreshToken === '') {
            return null;
        }

        $response = $this->send(new RefreshUserToken(
            clientId: $this->connector->clientId,
            clientSecret: $this->connector->getClientSecret(),
            refreshToken: $refreshToken,
        ));

        if ($response->serverError()) {
            throw TwitchUnreachable::while('refreshing the user token', $response->toException());
        }

        $accessToken = $response->json('access_token');

        if (!$response->successful() || !is_string($accessToken) || $accessToken === '') {
            return null;
        }

        $rotatedRefreshToken = $response->json('refresh_token');

        $identity->update(['credentials' => ClientAccessManager::make(
            accessToken: Crypt::encrypt($accessToken),
            refreshToken: Crypt::encrypt(is_string($rotatedRefreshToken) && $rotatedRefreshToken !== '' ? $rotatedRefreshToken : $refreshToken),
            expiresIn: Crypt::encrypt((string) ($response->json('expires_in') ?? '')),
        )]);

        return $this->scopesIn($response->json('scope'));
    }

    /**
     * @throws TwitchUnreachable
     */
    private function send(Request $request): Response
    {
        try {
            return $this->connector->send($request);
        } catch (FatalRequestException $fatalRequestException) {
            throw TwitchUnreachable::while('talking to the OAuth server', $fatalRequestException);
        }
    }

    /**
     * @return array<int, string>
     */
    private function scopesIn(mixed $scopes): array
    {
        return is_array($scopes) ? array_values(array_filter($scopes, is_string(...))) : [];
    }

    private function cacheKey(ExternalIdentity $identity): string
    {
        return 'twitch_user_authorization:'.$identity->getKey();
    }
}
