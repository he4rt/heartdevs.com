<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\DTOs;

final readonly class CreatePostDTO
{
    /**
     * @param  array<int, string>  $images
     */
    public function __construct(
        public string $userId,
        public string $content,
        public array $images = [],
    ) {}
}
