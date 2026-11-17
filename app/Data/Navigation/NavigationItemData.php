<?php

declare(strict_types=1);

namespace App\Data\Navigation;

use Spatie\LaravelData\Data;

final class NavigationItemData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $href,
        public bool $current,
    ) {}
}
