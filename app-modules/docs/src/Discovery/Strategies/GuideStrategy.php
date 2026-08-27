<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Legacy hand-written guides under `resources/docs/`. The manual index
 * (`documentation.md`) is excluded.
 */
final readonly class GuideStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Guide;
    }

    public function matches(SplFileInfo $file): bool
    {
        return str_contains($this->path($file), '/resources/docs/')
            && str_ends_with($file->getFilename(), '.md')
            && $file->getFilename() !== 'documentation.md';
    }

    protected function slug(SplFileInfo $file, DocumentMetadata $meta, ?string $module): string
    {
        return Str::of($file->getFilename())->beforeLast('.md')->slug()->value();
    }

    protected function moduleName(?string $module, DocumentMetadata $meta): ?string
    {
        return null;
    }
}
