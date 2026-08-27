<?php

declare(strict_types=1);

namespace He4rt\Economy\DTOs;

final readonly class DebitDTO
{
    public function __construct(
        public string $walletId,
        public int $amount,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $description = null,
    ) {}
}
