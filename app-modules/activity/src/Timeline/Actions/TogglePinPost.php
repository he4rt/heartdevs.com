<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Actions;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class TogglePinPost
{
    public function handle(User $user, Timeline $timeline): void
    {
        throw_if($timeline->user_id !== $user->id, AuthorizationException::class, 'You can only pin your own posts.');

        DB::transaction(function () use ($user, $timeline): void {
            if ($timeline->pinned) {
                Timeline::withoutTimestamps(fn () => $timeline->update(['pinned' => false]));

                return;
            }

            Timeline::withoutTimestamps(function () use ($user, $timeline): void {
                Timeline::query()
                    ->where('user_id', $user->id)
                    ->where('tenant_id', $timeline->tenant_id)
                    ->where('pinned', true)
                    ->update(['pinned' => false]);

                $timeline->update(['pinned' => true]);
            });
        });
    }
}
