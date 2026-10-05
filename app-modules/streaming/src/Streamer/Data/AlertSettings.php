<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

use He4rt\Streaming\Enums\StreamEventType;

final readonly class AlertSettings
{
    /**
     * @param  array<string, bool>  $enabled
     */
    public function __construct(
        private array $enabled = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $enabled = [];

        foreach (StreamEventType::cases() as $type) {
            if (is_bool($payload[$type->value] ?? null)) {
                $enabled[$type->value] = $payload[$type->value];
            }
        }

        return new self($enabled);
    }

    public function isEnabled(StreamEventType $type): bool
    {
        return $this->enabled[$type->value] ?? true;
    }

    public function with(StreamEventType $type, bool $enabled): self
    {
        return new self([...$this->enabled, $type->value => $enabled]);
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        $enabled = [];

        foreach (StreamEventType::cases() as $type) {
            $enabled[$type->value] = $this->isEnabled($type);
        }

        return $enabled;
    }
}
