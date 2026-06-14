<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Module entry points: a `README.md` at a module root. Discovery only ever
 * feeds module-scoped READMEs here (the project root README is never collected),
 * so matching by filename is sufficient.
 */
final readonly class ReadmeStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Module;
    }

    public function matches(SplFileInfo $file): bool
    {
        return $file->getFilename() === 'README.md';
    }

    protected function slug(SplFileInfo $file, DocumentMetadata $meta, ?string $module): string
    {
        return $module ?? 'readme';
    }

    protected function url(?string $module, string $slug, DocumentMetadata $meta): string
    {
        return '/docs/modules/'.($module ?? $slug);
    }
}
