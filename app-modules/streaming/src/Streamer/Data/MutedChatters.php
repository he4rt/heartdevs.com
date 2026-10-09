<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class MutedChatters
{
    /**
     * @param  list<MutedChatter>  $chatters
     */
    public function __construct(
        public array $chatters = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $chatters = [];

        foreach ($payload as $item) {
            $chatter = is_array($item) ? MutedChatter::fromArray($item) : null;

            if ($chatter instanceof MutedChatter) {
                $chatters[] = $chatter;
            }
        }

        return new self($chatters);
    }

    public function contains(IdentityProvider $platform, string $chatterId): bool
    {
        return array_any($this->chatters, fn (MutedChatter $chatter): bool => $chatter->is($platform, $chatterId));
    }

    public function with(MutedChatter $chatter): self
    {
        return new self([...$this->without($chatter->platform, $chatter->chatterId)->chatters, $chatter]);
    }

    public function without(IdentityProvider $platform, string $chatterId): self
    {
        return new self(array_values(array_filter($this->chatters, fn (MutedChatter $chatter): bool => !$chatter->is($platform, $chatterId))));
    }

    public function isEmpty(): bool
    {
        return $this->chatters === [];
    }

    /**
     * @return list<array{platform: string, chatter_id: string, display_name: string, muted_at: string}>
     */
    public function toArray(): array
    {
        return array_map(fn (MutedChatter $chatter): array => $chatter->toArray(), $this->chatters);
    }
}
