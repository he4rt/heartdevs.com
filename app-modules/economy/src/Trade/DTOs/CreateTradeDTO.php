<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\DTOs;

final readonly class CreateTradeDTO
{
    /**
     * @param  array<int, string>  $offeredItemIds
     * @param  array<int, string>  $requestedItemIds
     */
    public function __construct(
        public int $tenantId,
        public string $initiatorCharacterId,
        public string $receiverCharacterId,
        public array $offeredItemIds,
        public array $requestedItemIds,
    ) {}
}
