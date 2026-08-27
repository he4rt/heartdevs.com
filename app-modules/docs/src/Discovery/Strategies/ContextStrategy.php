<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Per-module domain glossary: `app-modules/{module}/CONTEXT.md`.
 */
final readonly class ContextStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Glossary;
    }

    public function matches(SplFileInfo $file): bool
    {
        return $file->getFilename() === 'CONTEXT.md';
    }

    protected function slug(SplFileInfo $file, DocumentMetadata $meta, ?string $module): string
    {
        return $module ?? 'context';
    }

    protected function url(?string $module, string $slug, DocumentMetadata $meta): string
    {
        return '/docs/glossary/'.($module ?? 'context');
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        return 1;
    }
}
