<?php

declare(strict_types=1);

namespace App\Data\Navigation;

use Spatie\LaravelData\Data;

final class NavigationGroupData extends Data
{
    /**
     * @param  array<int, NavigationItemData>  $items
     */
    public function __construct(
        public string $key,
        public ?string $heading,
        public array $items,
    ) {}
}
