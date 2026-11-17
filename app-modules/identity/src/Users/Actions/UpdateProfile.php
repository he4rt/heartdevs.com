<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\User;

final readonly class UpdateProfile
{
    /**
     * A new email is unverified until the user opens the link sent to it.
     */
    public function handle(User $user, string $name, string $email): void
    {
        $user->fill(['name' => $name, 'email' => $email]);
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
    }
}
