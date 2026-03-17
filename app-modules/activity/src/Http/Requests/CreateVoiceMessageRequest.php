<?php

declare(strict_types=1);

namespace He4rt\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateVoiceMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:twitch,discord'],
            'provider_id' => ['required'],
            'state' => ['required', 'in:muted,unmuted,disabled'],
            'channel_name' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['provider' => $this->route('provider')]);
    }
}
