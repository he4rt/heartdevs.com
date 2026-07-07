<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use Carbon\CarbonInterface;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Models\Profile;
use Illuminate\Validation\ValidationException;

final class UpsertProfile
{
    public function handle(Profile $profile, UpsertProfileDTO $dto): Profile
    {
        $this->validate($dto);

        $attributes = [];

        if ($dto->nickname !== null) {
            $attributes['nickname'] = $dto->nickname;
        }

        if ($dto->birthdate instanceof CarbonInterface) {
            $attributes['birthdate'] = $dto->birthdate;
        }

        if ($dto->about !== null) {
            $attributes['about'] = $dto->about;
        }

        if ($dto->headline !== null) {
            $attributes['headline'] = $dto->headline;
        }

        if ($dto->seniorityLevel instanceof SeniorityLevel) {
            $attributes['seniority_level'] = $dto->seniorityLevel;
        }

        if ($dto->yearsExperience !== null) {
            $attributes['years_experience'] = $dto->yearsExperience;
        }

        if ($dto->socialLinks !== null) {
            $attributes['social_links'] = $dto->socialLinks;
        }

        if ($dto->expectedSalary !== null) {
            $attributes['expected_salary'] = $dto->expectedSalary;
        }

        if ($dto->preferences instanceof WorkPreferences) {
            $attributes['preferences'] = $dto->preferences;
        }

        if ($attributes !== []) {
            $profile->update($attributes);
        }

        return $profile->refresh();
    }

    private function validate(UpsertProfileDTO $dto): void
    {
        $errors = [];

        if ($dto->about !== null && mb_strlen($dto->about) > 500) {
            $errors['about'] = [__('validation.max.string', ['attribute' => 'about', 'max' => 500])];
        }

        if ($dto->headline !== null && mb_strlen($dto->headline) > 100) {
            $errors['headline'] = [__('validation.max.string', ['attribute' => 'headline', 'max' => 100])];
        }

        if ($dto->nickname !== null && mb_strlen($dto->nickname) > 100) {
            $errors['nickname'] = [__('validation.max.string', ['attribute' => 'nickname', 'max' => 100])];
        }

        if ($dto->yearsExperience !== null && ($dto->yearsExperience < 0 || $dto->yearsExperience > 50)) {
            $errors['years_experience'] = [__('validation.between.numeric', ['attribute' => 'years_experience', 'min' => 0, 'max' => 50])];
        }

        if ($dto->expectedSalary !== null && (!is_numeric($dto->expectedSalary) || (float) $dto->expectedSalary < 0)) {
            $errors['expected_salary'] = [__('validation.min.numeric', ['attribute' => 'expected_salary', 'min' => 0])];
        }

        if ($dto->socialLinks !== null) {
            $validPlatforms = SocialPlatform::values();
            $invalidPlatforms = array_diff(array_keys($dto->socialLinks), $validPlatforms);

            if ($invalidPlatforms !== []) {
                $errors['social_links'] = [sprintf('Invalid social platform keys: %s.', implode(', ', $invalidPlatforms))];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
