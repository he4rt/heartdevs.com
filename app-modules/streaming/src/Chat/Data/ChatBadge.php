<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Data;

final readonly class ChatBadge
{
    public function __construct(
        public string $setId,
        public string $version,
        public ?string $url = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $setId = $payload['set_id'] ?? null;
        $version = $payload['version'] ?? null;

        if (!is_string($setId) || !is_string($version)) {
            return null;
        }

        return new self($setId, $version, is_string($payload['url'] ?? null) ? $payload['url'] : null);
    }

    /**
     * @return array{set_id: string, version: string, url: string|null}
     */
    public function toArray(): array
    {
        return [
            'set_id' => $this->setId,
            'version' => $this->version,
            'url' => $this->url,
        ];
    }

    /**
     * @return array{setId: string, version: string, url: string|null}
     */
    public function toBroadcast(): array
    {
        return [
            'setId' => $this->setId,
            'version' => $this->version,
            'url' => $this->url,
        ];
    }
}
