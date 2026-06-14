<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\Enums\DocumentType;

/**
 * A single documentation file discovered on the filesystem, parsed down to the
 * metadata needed for navigation. The HTML body is rendered lazily elsewhere.
 */
final readonly class DiscoveredDocument
{
    public function __construct(
        public DocumentType $type,
        public string $absolutePath,
        public string $slug,
        public string $url,
        public string $title,
        public ?string $moduleName = null,
        public ?CarbonImmutable $date = null,
        public int $order = 0,
        public bool $hidden = false,
        public ?string $author = null,
        public AdrMetadata|PlanMetadata|null $metadata = null,
    ) {}

    /**
     * URL section segment (e.g. "decisions").
     */
    public function section(): string
    {
        return $this->type->value;
    }

    /**
     * Whether this is a dated planning artifact that should carry a notice.
     */
    public function isDatedArtifact(): bool
    {
        return $this->type->isDatedArtifact();
    }
}
