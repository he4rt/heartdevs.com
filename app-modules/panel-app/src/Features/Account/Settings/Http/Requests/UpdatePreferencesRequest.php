<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests;

use App\Enums\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::enum(Language::class)],
            'theme_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function language(): Language
    {
        return Language::from($this->safe()->string('locale')->toString());
    }

    public function themeColor(): ?string
    {
        $themeColor = $this->safe()->string('theme_color')->lower()->toString();

        return $themeColor === '' ? null : $themeColor;
    }
}
