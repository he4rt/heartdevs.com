<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Contracts;

use Illuminate\Validation\ValidationException;

interface OnboardingStepDTO
{
    /**
     * Validate the raw payload and return a filled DTO instance.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function validate(array $payload): static;

    /**
     * The validated data ready to be persisted on the step.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array;
}
