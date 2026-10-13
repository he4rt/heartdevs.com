<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

final readonly class CoworkingSettings implements SceneSettings
{
    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self;
    }

    public function toArray(): array
    {
        return [];
    }
}
