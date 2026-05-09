<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class DeleteReply
{
    public function handle(User $user, Timeline $reply): void
    {
        throw_unless($reply->parent_id !== null, AuthorizationException::class, 'Only replies can be deleted.');
        throw_unless($reply->user_id === $user->id, AuthorizationException::class, 'You can only delete your own replies.');

        $postable = $reply->postable;

        $reply->delete();

        $postable?->forceDelete();
    }
}
