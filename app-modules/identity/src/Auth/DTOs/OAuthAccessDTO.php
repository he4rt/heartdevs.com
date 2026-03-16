<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

abstract class OAuthAccessDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public ?int $expiresIn
    ) {}

    abstract public static function make(array $payload): self;

    final public function toDatabase(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
        ];
    }
}
