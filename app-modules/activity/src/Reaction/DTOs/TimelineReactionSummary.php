<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\DTOs;

use He4rt\Activity\Reaction\Enums\TimelineReaction;

/**
 * Breakdown das reações de um único post da timeline web.
 *
 * `counts` é indexado pelo value de TimelineReaction e traz só reações com
 * contagem > 0; `mine` é a reação do usuário consultado naquele post, ou null.
 */
final readonly class TimelineReactionSummary
{
    /** @param array<string, int> $counts */
    public function __construct(
        public string $timelineId,
        public array $counts = [],
        public ?TimelineReaction $mine = null,
    ) {}

    public function total(): int
    {
        return array_sum($this->counts);
    }

    public function countOf(TimelineReaction $reaction): int
    {
        return $this->counts[$reaction->value] ?? 0;
    }
}
