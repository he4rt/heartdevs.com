<?php

declare(strict_types=1);

namespace He4rt\Moderation\Cases\Events;

use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Emitted when the auto-execution policy is satisfied: deterministic rule match + suggested action + known author.
 *
 * Platform listeners (e.g., AutoExecuteAction in bot-discord) react to this by creating a ModerationAction
 * and dispatching enforcement. The moderation module decides WHEN to emit — platforms decide HOW to enforce.
 */
final readonly class CaseReadyForEnforcement
{
    use Dispatchable;

    public function __construct(public ModerationCase $case) {}
}
