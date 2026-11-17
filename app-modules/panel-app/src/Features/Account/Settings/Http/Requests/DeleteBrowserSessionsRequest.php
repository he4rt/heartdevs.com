<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests;

use He4rt\Identity\Users\User;
use He4rt\PanelApp\Auth\RecentPasswordConfirmation;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteBrowserSessionsRequest extends FormRequest
{
    /**
     * A user without a password has nothing to type, so a recent confirmation stands in.
     */
    public function authorize(#[CurrentUser] User $user, RecentPasswordConfirmation $confirmation): Response
    {
        if ($user->hasPassword() || $confirmation->exists($this)) {
            return Response::allow();
        }

        return Response::deny('Confirm it is you before you log out your other sessions.');
    }

    /** @return array<string, mixed> */
    public function rules(#[CurrentUser] User $user): array
    {
        return $user->hasPassword()
            ? ['password' => ['required', 'string', 'current_password']]
            : [];
    }

    public function password(): ?string
    {
        return $this->safe()->has('password') ? $this->safe()->string('password')->toString() : null;
    }
}
