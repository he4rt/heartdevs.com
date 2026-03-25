<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVoiceMessageRequest extends FormRequest
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
            'provider' => ['required', 'in:twitch,discord'],
            'external_account_id' => ['required'],
            'state' => ['required', 'in:muted,unmuted,disabled'],
            'channel_name' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['provider' => $this->route('provider')]);
    }
}
