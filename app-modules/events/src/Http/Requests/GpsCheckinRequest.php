<?php

declare(strict_types=1);

namespace He4rt\Events\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class GpsCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'valid_coordinates_required',
            ], 400)
        );
    }
}
