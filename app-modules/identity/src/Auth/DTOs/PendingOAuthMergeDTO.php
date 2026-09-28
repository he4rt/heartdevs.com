<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class PendingOAuthMergeDTO
{
    public function __construct(
        public string $conflictingUserId,
        public OAuthConnectionDTO $connection,
    ) {}

    public static function fromSession(mixed $payload): ?self
    {
        if (!is_array($payload)) {
            return null;
        }

        $conflictingUserId = $payload['conflicting_user_id'] ?? null;
        $providerValue = $payload['provider'] ?? null;
        $providerId = $payload['provider_id'] ?? null;
        $credentials = $payload['credentials'] ?? null;
        $metadata = $payload['metadata'] ?? null;

        if (
            !is_string($conflictingUserId)
            || !is_string($providerValue)
            || !is_string($providerId)
            || !is_array($credentials)
            || !is_array($metadata)
        ) {
            return null;
        }

        $accessToken = $credentials['access_token'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;
        $expiresIn = $credentials['expires_in'] ?? null;
        $provider = IdentityProvider::tryFrom($providerValue);

        $hasValidTokens = is_string($accessToken)
            && is_string($refreshToken)
            && (is_string($expiresIn) || $expiresIn === null);
        $isOAuthProvider = $provider instanceof IdentityProvider
            && $provider->getCredentialsType() === CredentialsType::OAuth2;

        if (!$hasValidTokens || !$isOAuthProvider) {
            return null;
        }

        /** @var array<string, mixed> $metadata */
        return new self(
            conflictingUserId: $conflictingUserId,
            connection: new OAuthConnectionDTO(
                provider: $provider,
                providerId: $providerId,
                credentials: ClientAccessManager::make(
                    accessToken: $accessToken,
                    refreshToken: $refreshToken,
                    expiresIn: $expiresIn,
                ),
                metadata: $metadata,
            ),
        );
    }

    /**
     * @return array{
     *     conflicting_user_id: string,
     *     provider: string,
     *     provider_id: string,
     *     credentials: array{access_token: string|null, refresh_token: string|null, expires_in: int|string|null},
     *     metadata: array<string, mixed>,
     * }
     */
    public function toSession(): array
    {
        return [
            'conflicting_user_id' => $this->conflictingUserId,
            'provider' => $this->connection->provider->value,
            'provider_id' => $this->connection->providerId,
            'credentials' => [
                'access_token' => $this->connection->credentials->accessToken,
                'refresh_token' => $this->connection->credentials->refreshToken,
                'expires_in' => $this->connection->credentials->expiresIn,
            ],
            'metadata' => $this->connection->metadata,
        ];
    }
}
