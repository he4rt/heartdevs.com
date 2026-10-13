<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

use He4rt\Streaming\Enums\SubTier;

final readonly class GiftSubDetails implements StreamEventDetails
{
    public function __construct(
        public SubTier $tier = SubTier::Tier1,
        public int $total = 1,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $tier = is_string($payload['tier'] ?? null) ? SubTier::tryFrom($payload['tier']) : null;
        $total = is_int($payload['total'] ?? null) ? $payload['total'] : 1;

        return new self(
            tier: $tier ?? SubTier::Tier1,
            total: max(1, $total),
        );
    }

    public function toArray(): array
    {
        return [
            'tier' => $this->tier->value,
            'total' => $this->total,
        ];
    }

    public function summary(): string
    {
        return $this->total === 1 ? '1 sub' : sprintf('%d subs', $this->total);
    }
}
