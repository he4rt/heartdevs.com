<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Actions;

use He4rt\Gamification\Badge\Models\Badge;

final readonly class DeleteBadge
{
    public function handle(int $badgeId): void
    {
        Badge::query()->findOrFail($badgeId)->delete();
    }
}
