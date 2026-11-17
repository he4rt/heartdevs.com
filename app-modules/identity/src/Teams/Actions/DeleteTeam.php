<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use App\Support\FeatureFlags\Actions\ForgetFeatureFlags;
use He4rt\Identity\Teams\Team;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a team for good, with its memberships.
 */
final readonly class DeleteTeam
{
    public function __construct(private ForgetFeatureFlags $forgetFeatureFlags) {}

    public function handle(Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $this->forgetFeatureFlags->handle($team);
            $team->members()->detach();
            $team->forceDelete();
        });
    }
}
