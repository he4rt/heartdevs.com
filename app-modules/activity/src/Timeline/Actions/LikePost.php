<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Activity\Timeline\TimelineLike;
use He4rt\Identity\User\Models\User;

final readonly class LikePost
{
    public function handle(User $user, Timeline $post): void
    {
        TimelineLike::query()->firstOrCreate([
            'timeline_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }
}
