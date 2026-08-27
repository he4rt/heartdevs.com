<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\ActionType;

final readonly class SuggestedPenaltyDTO
{
    /**
     * @param  array<array<string, mixed>>  $history
     */
    public function __construct(
        public ActionType $action,
        public ?string $duration,
        public string $reasoning,
        public int $priorOffenses,
        public array $history,
    ) {}
}
