<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Contracts;

use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Classifies and parses one kind of documentation file. Strategies are
 * registered via container tagging; the first whose matches() returns true
 * owns the file.
 */
interface DocumentTypeStrategyContract
{
    public function type(): DocumentType;

    /**
     * Whether this strategy is responsible for the given file (path-based).
     */
    public function matches(SplFileInfo $file): bool;

    /**
     * Parse the file into a DiscoveredDocument (metadata only; HTML is lazy).
     */
    public function parse(SplFileInfo $file, ?string $moduleName): DiscoveredDocument;
}
