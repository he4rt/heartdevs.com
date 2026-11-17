<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Data;

use Spatie\LaravelData\Data;

final class EventFieldData extends Data
{
    public function __construct(
        public string $key,
        public string $value,
    ) {}
}
