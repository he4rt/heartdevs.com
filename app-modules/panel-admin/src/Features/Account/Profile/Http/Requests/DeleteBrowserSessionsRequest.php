<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteBrowserSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['password' => ['required', 'string', 'current_password']];
    }

    public function password(): string
    {
        return $this->safe()->string('password')->toString();
    }
}
