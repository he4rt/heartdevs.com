<?php

declare(strict_types=1);

namespace He4rt\Activity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMessageRequest extends FormRequest
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
            'tenant_id' => ['required'],
            'provider' => ['required', 'in:twitch,discord'],
            'external_account_id' => ['required'],
            'provider_message_id' => ['required'],
            'channel_id' => ['required'],
            'content' => ['required', 'string'],
            'sent_at' => ['required'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['provider' => $this->route('provider')]);
    }
}
