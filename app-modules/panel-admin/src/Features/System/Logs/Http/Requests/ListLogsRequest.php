<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Logs\Http\Requests;

use App\Support\Logging\WideEventKind;
use App\Support\Logging\WideEventOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'day' => ['nullable', 'date_format:Y-m-d'],
            'older_than' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'array'],
            'filter.search' => ['nullable', 'string', 'max:100'],
            'filter.kind' => ['nullable', Rule::enum(WideEventKind::class)],
            'filter.outcome' => ['nullable', Rule::enum(WideEventOutcome::class)],
            'filter.min_ms' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
