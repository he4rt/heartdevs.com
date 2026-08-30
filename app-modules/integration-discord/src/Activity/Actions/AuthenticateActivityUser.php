<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Activity\Actions;

use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\Identity\ExternalIdentity\Actions\FindConnectedUser;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationDiscord\Activity\DTOs\ActivityAuthResult;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;

/**
 * Troca o `code` do `authorize()` do SDK por token e resolve o User já vinculado a esse
 * Discord id. `user` vem null quando não há vínculo (estado esperado, não erro); o
 * `access_token` sempre volta preenchido — o client precisa dele pra `authenticate()`.
 */
final readonly class AuthenticateActivityUser
{
    public function __construct(
        private DiscordOAuthConnector $connector,
        private FindConnectedUser $findConnectedUser,
    ) {}

    public function execute(string $code): ActivityAuthResult
    {
        $tokenResponse = $this->connector->send(new ExchangeCodeForToken(
            code: $code,
            clientId: $this->connector->clientId,
            clientSecret: $this->connector->clientSecret,
        ));

        /** @var array<string, mixed> $tokenPayload */
        $tokenPayload = $tokenResponse->json();

        if (!isset($tokenPayload['access_token'])) {
            throw OAuthFlowException::tokenExchangeFailed('discord', (string) ($tokenPayload['error'] ?? 'unknown'));
        }

        $accessToken = (string) $tokenPayload['access_token'];

        $userResponse = $this->connector->send(new GetCurrentUser(
            accessToken: $accessToken,
        ));

        /** @var array<string, mixed> $userPayload */
        $userPayload = $userResponse->json();

        $user = $this->findConnectedUser->execute(IdentityProvider::Discord, (string) $userPayload['id']);

        return new ActivityAuthResult($accessToken, $user);
    }
}
