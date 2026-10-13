<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class MutedChatter
{
    public function __construct(
        public IdentityProvider $platform,
        public string $chatterId,
        public string $displayName,
        public CarbonImmutable $mutedAt,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $platform = is_string($payload['platform'] ?? null) ? IdentityProvider::tryFrom($payload['platform']) : null;
        $chatterId = $payload['chatter_id'] ?? null;
        $mutedAt = is_string($payload['muted_at'] ?? null) ? CarbonImmutable::parse($payload['muted_at']) : null;

        if (!$platform instanceof IdentityProvider || !is_string($chatterId) || $chatterId === '' || !$mutedAt instanceof CarbonImmutable) {
            return null;
        }

        return new self(
            platform: $platform,
            chatterId: $chatterId,
            displayName: is_string($payload['display_name'] ?? null) ? $payload['display_name'] : $chatterId,
            mutedAt: $mutedAt,
        );
    }

    public function is(IdentityProvider $platform, string $chatterId): bool
    {
        return $this->platform === $platform && $this->chatterId === $chatterId;
    }

    /**
     * @return array{platform: string, chatter_id: string, display_name: string, muted_at: string}
     */
    public function toArray(): array
    {
        return [
            'platform' => $this->platform->value,
            'chatter_id' => $this->chatterId,
            'display_name' => $this->displayName,
            'muted_at' => $this->mutedAt->toIso8601String(),
        ];
    }
}
