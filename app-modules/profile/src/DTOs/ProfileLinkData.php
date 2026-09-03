<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class ProfileLinkData
{
    public function __construct(
        public string $label,
        public string $handle,
        public string $icon,
        public ?string $url = null,
    ) {}
}
