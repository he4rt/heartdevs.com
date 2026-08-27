<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp\Ingest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class IngestEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'chat_jid' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ];
    }
}
