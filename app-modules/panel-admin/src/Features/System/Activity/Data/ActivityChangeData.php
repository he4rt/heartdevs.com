<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Activity\Data;

use Spatie\LaravelData\Data;

final class ActivityChangeData extends Data
{
    public function __construct(
        public string $field,
        public mixed $old,
        public mixed $new,
    ) {}
}
