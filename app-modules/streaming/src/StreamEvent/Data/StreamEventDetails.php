<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Data;

interface StreamEventDetails
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array;

    public function summary(): string;
}
