<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

/**
 * A feature that a super admin switches on per scope. Every flag starts off everywhere, with its gate closed.
 * The module that owns the feature declares the flag in its `src/Features/` folder and registers it in {@see FeatureRegistry}.
 */
interface FeatureFlag
{
    /**
     * The name Pennant stores. Never change it: the stored state is keyed by it.
     */
    public function key(): string;

    public function label(): string;

    public function description(): string;

    public function scope(): FeatureScope;
}
