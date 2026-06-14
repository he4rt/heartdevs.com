<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

use He4rt\Docs\Discovery\Enums\AdrStatus;

/**
 * Type-specific metadata for an Architecture Decision Record.
 */
final readonly class AdrMetadata
{
    /**
     * @param  list<string>  $deciders  GitHub handles
     * @param  list<array{label: string, target: string}>  $relations  e.g. "Builds on" / "Superseded by"
     */
    public function __construct(
        public AdrStatus $status,
        public array $deciders = [],
        public array $relations = [],
    ) {}
}
