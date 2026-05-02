<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\Platform;

final readonly class ExecutionResultDTO
{
    /**
     * @param  array<string, mixed>  $platformResponse
     */
    public function __construct(
        public Platform $platform,
        public bool $success,
        public ?string $error,
        public array $platformResponse,
    ) {}
}
