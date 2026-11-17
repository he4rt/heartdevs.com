<?php

declare(strict_types=1);

namespace App\Support\Logging;

/**
 * Events read from the end of a day file. `$olderThan` is the offset where the next page starts, or null at the start of the file.
 */
final readonly class EventLogPage
{
    /**
     * @param  array<int, EventLogLine>  $lines
     */
    public function __construct(
        public array $lines,
        public ?int $olderThan,
    ) {}
}
