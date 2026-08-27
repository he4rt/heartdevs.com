<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\GetAppAccessToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;

final readonly class TwitchAppTokenService
{
    public function __construct(
        private TwitchOAuthConnector $connector,
    ) {}

    public function getToken(): string
    {
        return Cache::remember('twitch_app_access_token', $this->getCacheTtl(), function (): string {
            $response = $this->connector->send(new GetAppAccessToken(
                clientId: $this->connector->clientId,
                clientSecret: $this->connector->getClientSecret(),
            ));

            $accessToken = $response->json('access_token');

            $tokenRequestFailed = !is_string($accessToken) || $accessToken === '';

            if ($tokenRequestFailed) {
                $reason = $response->json('message') ?? sprintf('unexpected response (HTTP %d)', $response->status());

                throw OAuthFlowException::tokenExchangeFailed('twitch', (string) $reason);
            }

            $expiresIn = (int) ($response->json('expires_in') ?? 3_600);

            Cache::put('twitch_app_token_expires_in', $expiresIn, $expiresIn);

            return $accessToken;
        });
    }

    private function getCacheTtl(): int
    {
        $expiresIn = (int) Cache::get('twitch_app_token_expires_in', 3_600);

        return max($expiresIn - 300, 60);
    }
}
