<?php

declare(strict_types=1);

namespace He4rt\Moderation\Actions;

use DomainException;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;

final readonly class FileAppeal
{
    public function handle(
        ModerationAction $action,
        User $appellant,
        string $reasonCategory,
        ?string $reasonText,
    ): ModerationAppeal {
        $windowDays = config('moderation.appeals.window_days', 7);
        $slaHours = config('moderation.appeals.sla_hours', 48);

        throw_if($action->created_at->diffInDays(now()) > $windowDays, DomainException::class, 'Appeal window has expired');

        throw_if($action->appeal()->exists(), DomainException::class, 'Appeal already exists for this action');

        return ModerationAppeal::query()->create([
            'action_id' => $action->id,
            'appellant_id' => $appellant->id,
            'reason_category' => $reasonCategory,
            'reason_text' => $reasonText,
            'status' => AppealStatus::Pending,
            'sla_deadline' => now()->addHours($slaHours),
        ]);
    }
}
