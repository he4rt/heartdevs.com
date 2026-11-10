<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Actions;

use He4rt\Activity\Reaction\DTOs\ReactWithDTO;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use Illuminate\Support\Facades\DB;

final readonly class ReactWith
{
    public function handle(ReactWithDTO $dto): ?TimelineReaction
    {
        return DB::transaction(static function () use ($dto): ?TimelineReaction {
            $reaction = UserReaction::query()->firstOrCreate(
                [
                    'user_id' => $dto->userId,
                    'timeline_id' => $dto->timelineId,
                ],
                ['reaction' => $dto->reaction],
            );
            if ($reaction->wasRecentlyCreated) {
                return $dto->reaction;
            }

            if ($reaction->reaction === $dto->reaction) {
                $reaction->delete();

                return null;
            }

            $reaction->update(['reaction' => $dto->reaction]);

            return $dto->reaction;
        });
    }
}
