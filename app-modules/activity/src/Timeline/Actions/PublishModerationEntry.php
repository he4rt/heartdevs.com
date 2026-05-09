<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Timeline\Timeline;

final readonly class PublishModerationEntry
{
    private const array PUBLISHABLE_TYPES = [
        ModerationType::Ban,
        ModerationType::Kick,
    ];

    public function handle(ModerationEvent $event): ?Timeline
    {
        if (!in_array($event->type, self::PUBLISHABLE_TYPES, true)) {
            return null;
        }

        $userId = $event->moderator?->model_id;

        if ($userId === null) {
            return null;
        }

        return Timeline::query()->create([
            'user_id' => $userId,
            'tenant_id' => $event->tenant_id,
            'postable_type' => $event->getMorphClass(),
            'postable_id' => $event->id,
        ]);
    }
}
