<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class ProfileProjectData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $url = null,
    ) {}
}
