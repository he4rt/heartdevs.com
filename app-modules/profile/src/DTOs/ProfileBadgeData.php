<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class ProfileBadgeData
{
    public function __construct(
        public string $name,
        public string $description,
        public ?string $imageUrl = null,
    ) {}
}
