<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

interface PenaltyAdvisorContract
{
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO;
}
