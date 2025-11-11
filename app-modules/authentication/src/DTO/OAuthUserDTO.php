<?php

declare(strict_types=1);

namespace He4rt\Authentication\DTO;

use He4rt\Authentication\Enums\OAuthProviderEnum;

abstract class OAuthUserDTO
{
    public function __construct(
        public OAuthAccessDTO $credentials,
        public string $providerId,
        public OAuthProviderEnum $provider,
        public string $username,
        public string $name,
        public ?string $email,
        public ?string $avatarUrl,
    ) {}

    abstract public static function make(OAuthAccessDTO $credentials, array $payload): self;

    final public function toDatabase(): array
    {
        return [
            'provider' => $this->provider,
            'provider_id' => $this->providerId,
            'email' => $this->email,
        ];
    }
}
