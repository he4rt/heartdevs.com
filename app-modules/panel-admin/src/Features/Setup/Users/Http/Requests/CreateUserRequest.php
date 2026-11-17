<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Requests;

use He4rt\Identity\Users\DTOs\NewUserData;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateUserRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('create', User::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    public function toDto(): NewUserData
    {
        return new NewUserData(
            name: $this->safe()->string('name')->trim()->toString(),
            email: $this->safe()->string('email')->trim()->lower()->toString(),
            password: $this->safe()->string('password')->toString(),
        );
    }
}
