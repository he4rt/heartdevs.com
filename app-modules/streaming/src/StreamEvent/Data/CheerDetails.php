<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

final readonly class CheerDetails implements StreamEventDetails
{
    public function __construct(
        public int $bits = 0,
        public ?string $message = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $bits = is_int($payload['bits'] ?? null) ? $payload['bits'] : 0;

        return new self(
            bits: max(0, $bits),
            message: is_string($payload['message'] ?? null) ? $payload['message'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'bits' => $this->bits,
            'message' => $this->message,
        ];
    }

    public function summary(): string
    {
        return sprintf('%s bits', number_format($this->bits, thousands_separator: '.'));
    }
}
