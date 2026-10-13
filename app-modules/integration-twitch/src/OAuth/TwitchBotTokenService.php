<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\RefreshUserToken;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ValidateToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;

final readonly class TwitchBotTokenService
{
    private const string ACCESS_TOKEN_CACHE_KEY = 'twitch_bot_access_token';

    private const string REFRESH_TOKEN_CACHE_KEY = 'twitch_bot_refresh_token';

    public function __construct(
        private TwitchOAuthConnector $connector,
    ) {}

    public static function userId(): ?string
    {
        return self::configured('user_id');
    }

    public static function isConfigured(): bool
    {
        return self::userId() !== null && self::configured('refresh_token') !== null;
    }

    public function getToken(): string
    {
        $cachedToken = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $refreshToken = Cache::get(self::REFRESH_TOKEN_CACHE_KEY) ?? self::configured('refresh_token');

        throw_unless(is_string($refreshToken), OAuthFlowException::tokenExchangeFailed('twitch', 'the bot account is not configured'));

        $response = $this->connector->send(new RefreshUserToken(
            clientId: $this->connector->clientId,
            clientSecret: $this->connector->getClientSecret(),
            refreshToken: $refreshToken,
        ));

        $accessToken = $response->json('access_token');

        if (!is_string($accessToken) || $accessToken === '') {
            $reason = $response->json('message') ?? sprintf('unexpected response (HTTP %d)', $response->status());

            throw OAuthFlowException::tokenExchangeFailed('twitch', (string) $reason);
        }

        $rotatedRefreshToken = $response->json('refresh_token');

        if (is_string($rotatedRefreshToken) && $rotatedRefreshToken !== '') {
            Cache::forever(self::REFRESH_TOKEN_CACHE_KEY, $rotatedRefreshToken);
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 3_600);
        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, max($expiresIn - 300, 60));

        return $accessToken;
    }

    /**
     * @return array<int, string>
     */
    public function grantedScopes(): array
    {
        $scopes = $this->connector->send(new ValidateToken($this->getToken()))->json('scopes');

        return is_array($scopes) ? array_values(array_filter($scopes, is_string(...))) : [];
    }

    private static function configured(string $key): ?string
    {
        $value = config('services.twitch.bot.'.$key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
