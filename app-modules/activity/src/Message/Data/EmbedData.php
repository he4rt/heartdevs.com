<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class EmbedData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $url = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $sourceDomain = null,
        public ?string $kind = null,
        public ?string $thumbnailUrl = null,
        public array $raw = [],
    ) {}
}
