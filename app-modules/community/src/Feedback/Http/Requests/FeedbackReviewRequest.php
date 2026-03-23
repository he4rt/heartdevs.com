<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Http\Requests;

use He4rt\Community\Feedback\Enums\ReviewTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

final class FeedbackReviewRequest extends FormRequest
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
            'action' => ['required', 'in:'.implode(',', ReviewTypeEnum::getTypes())],
            'staff_id' => ['required'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['action' => $this->route('action')]);
    }
}
