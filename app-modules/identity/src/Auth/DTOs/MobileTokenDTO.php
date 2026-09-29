<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

final readonly class MobileTokenDTO
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
    ) {}

    /**
     * @return array{access_token: string, token_type: string, expires_in: int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
