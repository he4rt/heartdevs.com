<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\DTOs;

final readonly class PurchaseItemDTO
{
    public function __construct(
        public string $characterId,
        public string $shopListingId,
    ) {}
}
