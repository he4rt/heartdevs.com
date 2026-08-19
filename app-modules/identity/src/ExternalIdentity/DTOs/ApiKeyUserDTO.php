<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

abstract class ApiKeyUserDTO
{
    public function __construct(
        public string $providerId,
        public IdentityProvider $provider,
        public string $username,
        public string $name,
        public ?string $email,
        public ?string $avatarUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    abstract public static function make(array $payload): self;

    /**
     * Mesma shape de metadata gravada pelo trilho OAuth em AttachProviderToUser,
     * para que `metadata->username` funcione igual nos dois fluxos.
     *
     * @return array<string, mixed>
     */
    final public function toMetadata(): array
    {
        return array_filter([
            'email' => $this->email,
            'avatar' => $this->avatarUrl,
            'username' => $this->username,
        ]);
    }
}
