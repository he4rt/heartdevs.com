<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

use SplFileInfo;

/**
 * A markdown file located by discovery, together with the module it belongs to
 * (null for system-wide documents).
 */
final readonly class DocumentSource
{
    public function __construct(
        public SplFileInfo $file,
        public ?string $moduleName,
    ) {}
}
