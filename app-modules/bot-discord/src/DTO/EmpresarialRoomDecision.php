<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\DTO;

use He4rt\BotDiscord\Enums\EmpresarialRejectionReason;

/**
 * Outcome of the Sala Empresarial decision seam: either a rejection reason or an
 * approved overwrite plan. Exactly one of {@see $rejection}/{@see $plan} is set.
 */
final readonly class EmpresarialRoomDecision
{
    private function __construct(
        public ?EmpresarialRejectionReason $rejection,
        public ?EmpresarialOverwritePlan $plan,
    ) {}

    public static function reject(EmpresarialRejectionReason $reason): self
    {
        return new self(rejection: $reason, plan: null);
    }

    public static function approve(EmpresarialOverwritePlan $plan): self
    {
        return new self(rejection: null, plan: $plan);
    }

    public function isApproved(): bool
    {
        return $this->plan instanceof EmpresarialOverwritePlan;
    }
}
