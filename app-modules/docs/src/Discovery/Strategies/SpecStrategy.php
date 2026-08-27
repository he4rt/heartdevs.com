<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;

/**
 * Design specs: files in any `docs/specs` directory (and the legacy `docs/superpowers/specs`).
 */
final readonly class SpecStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Spec;
    }

    public function matches(SplFileInfo $file): bool
    {
        $path = $this->path($file);

        return (str_contains($path, '/docs/specs/') || str_contains($path, '/superpowers/specs/'))
            && str_ends_with($file->getFilename(), '.md');
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        $date = $this->date($file, $meta);

        return $date instanceof CarbonImmutable ? -$date->getTimestamp() : 0;
    }
}
