<?php

declare(strict_types=1);

namespace He4rt\Moderation\Actions;

use DomainException;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAppeal;

final readonly class ReviewAppeal
{
    public function handle(
        ModerationAppeal $appeal,
        User $reviewer,
        AppealStatus $decision,
        string $notes,
    ): void {
        $originalModeratorId = $appeal->action->moderator_id;

        throw_if($reviewer->id === $originalModeratorId, DomainException::class, 'Reviewer must be different from original moderator');

        $appeal->update([
            'status' => $decision,
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }
}
