<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use Spatie\LaravelData\Data;

final class PermissionMatrixData extends Data
{
    /**
     * @param  array<int, PermissionActionData>  $actions
     * @param  array<int, PermissionGroupData>  $groups
     */
    public function __construct(
        public array $actions,
        public array $groups,
        public int $total,
    ) {}
}
