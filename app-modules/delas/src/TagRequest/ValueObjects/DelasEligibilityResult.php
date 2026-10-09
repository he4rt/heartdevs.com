<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\ValueObjects;

use Carbon\CarbonInterface;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;

final readonly class DelasEligibilityResult
{
    public function __construct(
        public DelasEligibilityState $state,
        public ?DelasTagRequest $request = null,
        public ?CarbonInterface $nextAllowedAt = null,
    ) {}

    public function canRequest(): bool
    {
        return $this->state === DelasEligibilityState::CanRequest;
    }

    public function hasTag(): bool
    {
        return $this->state === DelasEligibilityState::Member;
    }
}
