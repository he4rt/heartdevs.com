<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

/**
 * Suggests an appropriate penalty based on violation context and user history.
 *
 * The advisor is purely advisory — it returns a suggestion that moderators can
 * accept, override, or ignore. It never executes actions directly.
 *
 * Implementations should consider:
 * - Number of prior offenses within the configured escalation window
 * - Severity of the current violation
 * - Pattern of past violations (escalation trajectory)
 *
 * The suggestion includes reasoning text so moderators understand WHY a particular
 * penalty is recommended, enabling informed decision-making.
 *
 * @see Advisors\HistoryBasedPenaltyAdvisor
 */
interface PenaltyAdvisorContract
{
    /**
     * Suggest a penalty for the given user based on the violation type and severity.
     *
     * Returns a DTO containing the recommended action, duration, reasoning, prior
     * offense count, and a summary of recent moderation history for the user.
     */
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO;
}
