<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

use He4rt\Streaming\Enums\SubTier;

final readonly class SubDetails implements StreamEventDetails
{
    public function __construct(
        public SubTier $tier = SubTier::Tier1,
        public int $months = 1,
        public ?string $message = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $tier = is_string($payload['tier'] ?? null) ? SubTier::tryFrom($payload['tier']) : null;
        $months = is_int($payload['months'] ?? null) ? $payload['months'] : 1;

        return new self(
            tier: $tier ?? SubTier::Tier1,
            months: max(1, $months),
            message: is_string($payload['message'] ?? null) ? $payload['message'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'tier' => $this->tier->value,
            'months' => $this->months,
            'message' => $this->message,
        ];
    }

    public function summary(): string
    {
        return $this->months === 1 ? '1 mês' : sprintf('%d meses', $this->months);
    }
}
