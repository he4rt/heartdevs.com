<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use Illuminate\Support\Facades\Crypt;

abstract class OAuthAccessDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public ?int $expiresIn
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    abstract public static function make(array $payload): self;

    /**
     * @return array<string, mixed>
     */
    final public function toDatabase(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
        ];
    }

    final public function toClientAccessManager(): ClientAccessManager
    {
        return ClientAccessManager::make(
            accessToken: Crypt::encrypt($this->accessToken),
            refreshToken: Crypt::encrypt($this->refreshToken),
            expiresIn: $this->expiresIn !== null ? Crypt::encrypt((string) $this->expiresIn) : null,
        );
    }
}
