<?php

declare(strict_types=1);

namespace He4rt\Economy\DTOs;

final readonly class TransferDTO
{
    public function __construct(
        public string $fromWalletId,
        public string $toWalletId,
        public int $amount,
        public ?string $description = null,
    ) {}
}
