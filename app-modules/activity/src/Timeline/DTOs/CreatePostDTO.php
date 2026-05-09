<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class CreatePostDTO
{
    /**
     * @param  array<int, UploadedFile>  $images
     */
    public function __construct(
        public string $userId,
        public int $tenantId,
        public string $content,
        public array $images = [],
    ) {}
}
