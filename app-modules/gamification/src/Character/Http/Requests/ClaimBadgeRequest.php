<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ClaimBadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'redeem_code' => ['required', 'string'],
        ];
    }
}
