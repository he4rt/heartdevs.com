<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use App\Support\FeatureFlags\Actions\ForgetFeatureFlags;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\Exceptions\UserOwnsTeams;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

/**
 * A user leaves every team on the way out. A team owner must hand the team over first, because a team always has an owner.
 */
final readonly class DeleteUser
{
    public function __construct(private ForgetFeatureFlags $forgetFeatureFlags) {}

    public function handle(User $user): void
    {
        $ownsTeams = Team::withTrashed()->where('owner_id', $user->id)->exists();

        throw_if($ownsTeams, UserOwnsTeams::make());

        DB::transaction(function () use ($user): void {
            $this->forgetFeatureFlags->handle($user);
            $user->tenants()->detach();
            $user->delete();
        });
    }
}
