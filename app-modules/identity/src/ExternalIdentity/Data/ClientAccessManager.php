<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Data;

use Illuminate\Support\Facades\Crypt;

class ClientAccessManager
{
    public function __construct(
        public ?string $clientId = null,
        public ?string $clientSecret = null,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public int|string|null $expiresIn = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $apiKey = null,
    ) {}

    public static function make(
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        int|string|null $expiresIn = null,
        ?string $username = null,
        ?string $password = null,
        ?string $apiKey = null,
    ): self {
        return new self($clientId, $clientSecret, $accessToken, $refreshToken, $expiresIn, $username, $password, $apiKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): self
    {
        return new self(
            clientId: $payload['client_id'] ?? null,
            clientSecret: $payload['client_secret'] ?? null,
            accessToken: $payload['access_token'] ?? null,
            refreshToken: $payload['refresh_token'] ?? null,
            expiresIn: $payload['expires_in'] ?? null,
            username: $payload['username'] ?? null,
            password: $payload['password'] ?? null,
            apiKey: $payload['api_key'] ?? null,
        );
    }

    public function getClientId(): ?string
    {
        return $this->clientId !== null ? Crypt::decrypt($this->clientId) : null;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken !== null ? Crypt::decrypt($this->accessToken) : null;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken !== null ? Crypt::decrypt($this->refreshToken) : null;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn !== null ? (int) Crypt::decrypt($this->expiresIn) : null;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret !== null ? Crypt::decrypt($this->clientSecret) : null;
    }

    public function getUsername(): ?string
    {
        return $this->username !== null ? Crypt::decrypt($this->username) : null;
    }

    public function getPassword(): ?string
    {
        return $this->password !== null ? Crypt::decrypt($this->password) : null;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey !== null ? Crypt::decrypt($this->apiKey) : null;
    }
}
