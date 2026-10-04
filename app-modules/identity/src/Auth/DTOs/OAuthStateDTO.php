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
    /**
     * @param  array<int, string>  $features
     */
    public function __construct(
        public OAuthIntent $intent,
        public IdentityProvider $provider,
        public string $panel,
        public ?string $returnUrl = null,
        public ?string $nonce = null,
        public array $features = [],
    ) {}

    public function __toString(): string
    {
        return Crypt::encryptString(json_encode($this, JSON_THROW_ON_ERROR));
    }

    public static function fromEncryptedString(string $state): self
    {
        $data = json_decode(Crypt::decryptString($state), associative: true);

        return new self(
            intent: OAuthIntent::from($data['intent']),
            provider: IdentityProvider::from($data['provider']),
            panel: $data['panel'],
            returnUrl: $data['return_url'] ?? null,
            nonce: $data['nonce'] ?? null,
            features: self::featuresFrom($data['features'] ?? []),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function featuresFrom(mixed $features): array
    {
        return is_array($features) ? array_values(array_filter($features, is_string(...))) : [];
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
            'return_url' => $this->returnUrl,
            'nonce' => $this->nonce,
            'features' => $this->features,
        ];
    }
}
