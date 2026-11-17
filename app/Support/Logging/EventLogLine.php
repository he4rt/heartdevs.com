<?php

declare(strict_types=1);

namespace App\Support\Logging;

/**
 * One wide event read back from the file of a day. The byte offset of its line finds it again: the file only grows.
 */
final readonly class EventLogLine
{
    /**
     * @param  array<array-key, mixed>  $event
     */
    public function __construct(
        public string $day,
        public int $offset,
        public array $event,
    ) {}
}
