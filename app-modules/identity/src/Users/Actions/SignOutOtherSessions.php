<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class SignOutOtherSessions
{
    /**
     * Rehashing the password ends the other sessions behind AuthenticateSession. The database driver also
     * keeps the session rows, so they go too. A user without a password has no hash to rehash, so only the
     * rows end their sessions.
     */
    public function handle(User $user, ?string $password, string $currentSessionId): void
    {
        if ($password !== null) {
            Auth::logoutOtherDevices($password);
        }

        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config()->string('session.table'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
