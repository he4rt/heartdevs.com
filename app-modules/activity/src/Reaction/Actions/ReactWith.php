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
            $existing = UserReaction::query()
                ->where('user_id', $dto->userId)
                ->where('timeline_id', $dto->timelineId)
                ->first();

            if ($existing === null) {
                UserReaction::query()->create([
                    'user_id' => $dto->userId,
                    'timeline_id' => $dto->timelineId,
                    'reaction' => $dto->reaction,
                ]);

                return $dto->reaction;
            }

            if ($existing->reaction === $dto->reaction) {
                $existing->delete();

                return null;
            }

            $existing->update(['reaction' => $dto->reaction]);

            return $dto->reaction;
        });
    }
}
