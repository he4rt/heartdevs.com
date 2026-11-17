<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Requests;

use He4rt\Identity\Users\DTOs\UserData;
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
        ];
    }

    public function toDto(): UserData
    {
        return new UserData(
            name: $this->safe()->string('name')->trim()->toString(),
            email: $this->safe()->string('email')->trim()->lower()->toString(),
        );
    }
}
