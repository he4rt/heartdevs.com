<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Data;

use Spatie\LaravelData\Data;

final class DocNodeData extends Data
{
    /**
     * @param  array<int, DocNodeData>  $children
     */
    public function __construct(
        public string $path,
        public string $title,
        public string $url,
        public array $children,
    ) {}
}
