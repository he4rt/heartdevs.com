<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\DTOs\PlanMetadata;
use He4rt\Docs\Discovery\Enums\DocumentType;
use He4rt\Docs\Discovery\Enums\PlanStatus;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Implementation plans: files in any `docs/plans` directory (and the legacy
 * `docs/superpowers/plans`). Status is derived from task checkboxes.
 */
final readonly class PlanStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Plan;
    }

    public function matches(SplFileInfo $file): bool
    {
        $path = $this->path($file);

        return (str_contains($path, '/docs/plans/') || str_contains($path, '/superpowers/plans/'))
            && str_ends_with($file->getFilename(), '.md');
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        $date = $this->date($file, $meta);

        return $date instanceof CarbonImmutable ? -$date->getTimestamp() : 0;
    }

    protected function metadata(string $content, DocumentMetadata $meta): PlanMetadata
    {
        $total = (int) preg_match_all('/^\s*-\s*\[[ xX]\]/m', $content);
        $done = (int) preg_match_all('/^\s*-\s*\[[xX]\]/m', $content);

        $declared = $meta->string('status');
        $status = $declared !== null
            ? (PlanStatus::tryFrom(Str::snake($declared)) ?? PlanStatus::fromProgress($done, $total))
            : PlanStatus::fromProgress($done, $total);

        return new PlanMetadata($status, $done, $total);
    }
}
