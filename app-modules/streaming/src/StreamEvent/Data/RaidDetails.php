<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

final readonly class RaidDetails implements StreamEventDetails
{
    public function __construct(
        public int $viewers = 0,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $viewers = is_int($payload['viewers'] ?? null) ? $payload['viewers'] : 0;

        return new self(max(0, $viewers));
    }

    public function toArray(): array
    {
        return [
            'viewers' => $this->viewers,
        ];
    }

    public function summary(): string
    {
        return sprintf('+%d viewers', $this->viewers);
    }
}
