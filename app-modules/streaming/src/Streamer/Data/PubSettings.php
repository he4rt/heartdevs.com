<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

final readonly class PubSettings implements SceneSettings
{
    private const string EVENT_HASH_PATTERN = '/^[A-Za-z0-9]{6,64}$/';

    public ?string $slidoEventHash;

    public function __construct(?string $slidoEventHash = null)
    {
        $isValidHash = $slidoEventHash !== null && preg_match(self::EVENT_HASH_PATTERN, $slidoEventHash) === 1;

        $this->slidoEventHash = $isValidHash ? $slidoEventHash : null;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $hash = $payload['slido_event_hash'] ?? null;

        return new self(is_string($hash) ? $hash : null);
    }

    /**
     * @return array{slido_event_hash: string|null}
     */
    public function toArray(): array
    {
        return [
            'slido_event_hash' => $this->slidoEventHash,
        ];
    }
}
