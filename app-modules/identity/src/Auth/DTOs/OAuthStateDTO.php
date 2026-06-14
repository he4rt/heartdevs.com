<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\DTOs;

use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Support\Facades\Crypt;
use JsonSerializable;
use Stringable;

final readonly class OAuthStateDTO implements JsonSerializable, Stringable
{
    public function __construct(
        public OAuthIntent $intent,
        public IdentityProvider $provider,
        public string $panel,
        public string $tenant,
        public ?string $returnUrl = null,
    ) {}

    public function __toString(): string
    {
        return Crypt::encryptString(json_encode($this, JSON_THROW_ON_ERROR));
    }

    public static function fromEncryptedString(string $state): self
    {
        $data = json_decode(Crypt::decryptString($state), true);

        return new self(
            intent: OAuthIntent::from($data['intent']),
            provider: IdentityProvider::from($data['provider']),
            panel: $data['panel'],
            tenant: $data['tenant'],
            returnUrl: $data['return_url'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'intent' => $this->intent->value,
            'provider' => $this->provider->value,
            'panel' => $this->panel,
            'tenant' => $this->tenant,
            'return_url' => $this->returnUrl,
        ];
    }
}
