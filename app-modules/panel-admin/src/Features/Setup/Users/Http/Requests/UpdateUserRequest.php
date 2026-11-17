<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Requests;

use He4rt\Identity\Users\DTOs\UserData;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'user')] User $target, #[CurrentUser] User $actor): bool
    {
        return $actor->can('update', $target);
    }

    /** @return array<string, mixed> */
    public function rules(#[RouteParameter(parameter: 'user')] User $target): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($target)],
            'password' => ['nullable', 'string', Password::defaults()],
        ];
    }

    /**
     * An empty password keeps the current one.
     */
    public function toDto(): UserData
    {
        $password = $this->safe()->string('password')->toString();

        return new UserData(
            name: $this->safe()->string('name')->trim()->toString(),
            email: $this->safe()->string('email')->trim()->lower()->toString(),
            password: $password === '' ? null : $password,
        );
    }
}
