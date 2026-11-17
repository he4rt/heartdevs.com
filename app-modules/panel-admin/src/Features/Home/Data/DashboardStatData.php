<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Home\Data;

use Spatie\LaravelData\Data;

final class DashboardStatData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public int $value,
    ) {}
}
