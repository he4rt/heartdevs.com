<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Roles\Data;

use Spatie\LaravelData\Data;

final class PermissionActionData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}
}
