<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Date;

final class UpsertProfile
{
    public function handle(Profile $profile, UpsertProfileDTO $dto): Profile
    {
        $this->validate($dto);

        $attributes = $dto->toDatabase();

        if ($attributes !== []) {
            $profile->update($attributes);
        }

        return $profile->refresh();
    }

    private function validate(UpsertProfileDTO $dto): void
    {
        Validator::make(
            [
                'nickname' => $dto->nickname,
                'headline' => $dto->headline,
                'birthdate' => $dto->birthdate,
                'about' => $dto->about,
                'years_experience' => $dto->yearsExperience,
                'expected_salary_min' => $dto->expectedSalaryMin,
                'expected_salary_max' => $dto->expectedSalaryMax,
                'social_links' => $dto->socialLinks,
            ],
            [
                'nickname' => ['nullable', 'string', 'max:100'],
                'birthdate' => ['nullable', 'date', (new Date)->after('1900-01-01')->beforeToday()],
                'headline' => ['nullable', 'string', 'max:100'],
                'about' => ['nullable', 'string', 'max:500'],
                'years_experience' => ['nullable', 'integer', 'between:0,50'],
                'expected_salary_min' => ['nullable', 'numeric', 'min:0'],
                'expected_salary_max' => ['nullable', 'numeric', 'min:0', 'gte:expected_salary_min'],
                'social_links' => ['nullable', 'array:'.implode(',', SocialPlatform::values())],
            ],
        )->validate();

    }
}
