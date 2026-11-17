<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function password(): string
    {
        return $this->safe()->string('password')->toString();
    }
}
