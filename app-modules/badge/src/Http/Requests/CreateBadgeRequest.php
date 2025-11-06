<?php

declare(strict_types=1);

namespace He4rt\Badge\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:discord,twitch'],
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'redeem_code' => ['required', 'string'],
            'active' => ['required', 'boolean'],
            'tenant_id' => ['required', 'int'],
        ];
    }
}
