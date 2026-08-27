<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Product Requirements Documents: files in any `docs/prd` directory.
 */
final readonly class PrdStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Prd;
    }

    public function matches(SplFileInfo $file): bool
    {
        return str_contains($this->path($file), '/docs/prd/')
            && str_ends_with($file->getFilename(), '.md');
    }

    protected function title(SplFileInfo $file, DocumentMetadata $meta): string
    {
        return (string) preg_replace('/^PRD\s*[:\-—]\s*/i', '', $meta->title);
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        $date = $this->date($file, $meta);

        return $date instanceof CarbonImmutable ? -$date->getTimestamp() : 0;
    }
}
