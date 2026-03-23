<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
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
            'info' => ['array', 'required'],
            'info.name' => ['string', 'max:100'],
            'info.nickname' => ['string', 'max:100'],
            'info.linkedin_url' => ['string'],
            'info.github_url' => ['string'],
            'info.birthdate' => ['string'],
            'info.about' => ['string'],
        ];
    }
}
