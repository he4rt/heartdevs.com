<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Data;

/**
 * A single state's slice of the located-members total (a "Top 5" row).
 */
final readonly class StateShare
{
    public function __construct(
        public string $name,
        public int $members,
        public float $share,
    ) {}
}
