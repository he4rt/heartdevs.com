<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Auth;

use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    /** @param  array<string, mixed>  $input */
    public function reset(User $user, array $input): void
    {
        /** @var array{password: string} $validated */
        $validated = Validator::make($input, [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ])->validate();

        $user->forceFill(['password' => $validated['password']])->save();
    }
}
