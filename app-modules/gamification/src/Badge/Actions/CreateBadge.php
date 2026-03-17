<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Actions;

use He4rt\Gamification\Badge\DTOs\NewBadgeDTO;

final readonly class CreateBadge
{
    public function __construct(private PersistBadge $persistBadge) {}

    public function handle(array $payload): mixed
    {
        $newBadgeDTO = NewBadgeDTO::make($payload);

        return $this->persistBadge->handle($newBadgeDTO);
    }
}
