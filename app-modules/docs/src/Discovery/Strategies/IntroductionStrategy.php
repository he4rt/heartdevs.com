<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Institutional, system-wide introduction pages: files under the repo root
 * `docs/introduction/` directory. These are curated, indexable entry points
 * and carry no module scope.
 */
final readonly class IntroductionStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Introduction;
    }

    public function matches(SplFileInfo $file): bool
    {
        return str_contains($this->path($file), '/docs/introduction/')
            && str_ends_with($file->getFilename(), '.md');
    }

    protected function moduleName(?string $module, DocumentMetadata $meta): ?string
    {
        return null;
    }
}
