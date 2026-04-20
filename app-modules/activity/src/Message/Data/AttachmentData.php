<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class AttachmentData
{
    public function __construct(
        public string $url,
        public ?string $filename = null,
        public ?string $contentType = null,
        public ?int $size = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}
}
