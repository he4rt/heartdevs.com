<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Data;

use Spatie\LaravelData\Data;

final class SettingsTabData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $href,
        public bool $current,
    ) {}
}
