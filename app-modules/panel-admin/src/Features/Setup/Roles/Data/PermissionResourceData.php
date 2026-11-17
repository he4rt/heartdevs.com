<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use Spatie\LaravelData\Data;

final class PermissionResourceData extends Data
{
    /**
     * @param  array<string, string>  $permissions  action => permission id
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $permissions,
    ) {}
}
