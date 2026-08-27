<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Actions;

use He4rt\Gamification\Badge\DTOs\NewBadgeDTO;
use He4rt\Gamification\Badge\Models\Badge;

final readonly class PersistBadge
{
    public function handle(NewBadgeDTO $badgeDTO): Badge
    {
        return Badge::query()->create($badgeDTO->jsonSerialize());
    }
}
