<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use Spatie\LaravelData\Data;

final class PermissionGroupData extends Data
{
    /**
     * @param  array<int, PermissionResourceData>  $resources
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $resources,
    ) {}
}
