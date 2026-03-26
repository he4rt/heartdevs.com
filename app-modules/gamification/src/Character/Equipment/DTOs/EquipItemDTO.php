<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Equipment\DTOs;

final readonly class EquipItemDTO
{
    public function __construct(
        public string $characterId,
        public string $characterItemId,
    ) {}
}
