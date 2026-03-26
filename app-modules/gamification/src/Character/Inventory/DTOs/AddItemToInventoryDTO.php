<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Inventory\DTOs;

use He4rt\Gamification\Item\Enums\AcquisitionMethod;

final readonly class AddItemToInventoryDTO
{
    public function __construct(
        public string $characterId,
        public string $itemId,
        public int $tenantId,
        public AcquisitionMethod $acquiredVia,
    ) {}
}
