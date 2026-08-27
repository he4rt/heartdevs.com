<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * The system-wide context map: `CONTEXT-MAP.md` at the repo root.
 */
final readonly class ContextMapStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Glossary;
    }

    public function matches(SplFileInfo $file): bool
    {
        return $file->getFilename() === 'CONTEXT-MAP.md';
    }

    protected function slug(SplFileInfo $file, DocumentMetadata $meta, ?string $module): string
    {
        return 'context-map';
    }

    protected function url(?string $module, string $slug, DocumentMetadata $meta): string
    {
        return '/docs/glossary/context-map';
    }

    protected function moduleName(?string $module, DocumentMetadata $meta): ?string
    {
        return null;
    }
}
