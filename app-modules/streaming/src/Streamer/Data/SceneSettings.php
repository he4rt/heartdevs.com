<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Data;

interface SceneSettings
{
    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array;
}
