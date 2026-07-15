<?php

declare(strict_types=1);

namespace He4rt\Onboarding\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class WelcomeFormDTO
{
    /**
     * TODO: Verificar quais campos devem estar presentes
     * dentro do DTO
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @throws ValidationException
     */
    public static function validate(array $payload): self
    {
        $validated = Validator::make($payload, [
            'data' => ['required', 'array', 'min:1'],
        ])->validate();

        return new self(
            data: $validated['data'],
        );
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
