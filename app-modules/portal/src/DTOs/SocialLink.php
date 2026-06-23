<?php

declare(strict_types=1);

namespace He4rt\Portal\DTOs;

final readonly class SocialLink
{
    public function __construct(
        public string $label,
        public string $url,
        public string $icon,
        public string $accent,
        public ?string $accentDark = null,
    ) {}
}
