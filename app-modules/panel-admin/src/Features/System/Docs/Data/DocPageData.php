<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Data;

use Spatie\LaravelData\Data;

final class DocPageData extends Data
{
    /**
     * @param  array<int, DocNodeData>  $children  pages of a group, listed under its text
     */
    public function __construct(
        public string $path,
        public string $title,
        public string $html,
        public string $locale,
        public bool $isFallback,
        public array $children,
    ) {}
}
