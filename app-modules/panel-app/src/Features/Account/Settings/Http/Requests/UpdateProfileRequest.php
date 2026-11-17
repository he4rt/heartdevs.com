<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests;

use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(#[CurrentUser] User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
        ];
    }

    public function name(): string
    {
        return $this->safe()->string('name')->toString();
    }

    public function email(): string
    {
        return $this->safe()->string('email')->toString();
    }
}
