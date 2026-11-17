<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Auth;

use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    /** @param  array<string, mixed>  $input */
    public function create(array $input): User
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ])->validate();

        return User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
    }
}
