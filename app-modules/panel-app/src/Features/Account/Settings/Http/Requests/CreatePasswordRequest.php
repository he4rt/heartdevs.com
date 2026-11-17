<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests;

use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * The first password of a user who signed up with a provider. A user with a password changes it instead.
 */
final class CreatePasswordRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $user): bool
    {
        return !$user->hasPassword();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function password(): string
    {
        return $this->safe()->string('password')->toString();
    }
}
