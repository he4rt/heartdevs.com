<?php

declare(strict_types=1);

namespace He4rt\Onboarding\DTOs;

use He4rt\Onboarding\Contracts\OnboardingStepDTO;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class WelcomeFormDTO implements OnboardingStepDTO
{
    /**
     * TODO: Verificar quais campos devem estar presentes
     * dentro do DTO
     *
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(
        public array $data = [],
    ) {}

    /**
     * @throws ValidationException
     */
    public function validate(array $payload): static
    {
        $validated = Validator::make($payload, [
            'data' => ['required', 'array', 'min:1'],
        ])->validate();

        $data = $validated['data'];

        return new self(
            data: is_array($data) ? $data : [],
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
