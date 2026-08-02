<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

use Carbon\CarbonInterface;
use He4rt\Profile\Data\WorkPreferences;
use He4rt\Profile\Enums\SeniorityLevel;
use Illuminate\Support\Facades\Date;

final readonly class UpsertProfileDTO
{
    public function __construct(
        public ?string $nickname = null,
        public ?CarbonInterface $birthdate = null,
        public ?string $about = null,
        public ?string $headline = null,
        public ?SeniorityLevel $seniorityLevel = null,
        public ?int $yearsExperience = null,
        /** @var array<string, string>|null */
        public ?array $socialLinks = null,
        public ?string $expectedSalaryMin = null,
        public ?string $expectedSalaryMax = null,
        public ?WorkPreferences $preferences = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nickname: $data['nickname'] ?? null,
            birthdate: isset($data['birthdate']) ? Date::parse($data['birthdate']) : null,
            about: $data['about'] ?? null,
            headline: $data['headline'] ?? null,
            seniorityLevel: isset($data['seniority_level'])
                ? ($data['seniority_level'] instanceof SeniorityLevel ? $data['seniority_level'] : SeniorityLevel::from($data['seniority_level']))
                : null,
            yearsExperience: isset($data['years_experience']) ? (int) $data['years_experience'] : null,
            socialLinks: $data['social_links'] ?? null,
            expectedSalaryMin: isset($data['expected_salary_min']) && $data['expected_salary_min'] !== ''
                ? (string) $data['expected_salary_min']
                : null,
            expectedSalaryMax: isset($data['expected_salary_max']) && $data['expected_salary_max'] !== ''
                ? (string) $data['expected_salary_max']
                : null,
            preferences: isset($data['preferences'])
                ? ($data['preferences'] instanceof WorkPreferences
                    ? $data['preferences']
                    : WorkPreferences::makeFromPayload((array) $data['preferences']))
                : null,
        );
    }

    /**
     * Maps the set (non-null) fields to their persistence column names.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(): array
    {
        return array_filter(
            [
                'nickname' => $this->nickname,
                'birthdate' => $this->birthdate,
                'about' => $this->about,
                'headline' => $this->headline,
                'seniority_level' => $this->seniorityLevel,
                'years_experience' => $this->yearsExperience,
                'social_links' => $this->socialLinks,
                'expected_salary_min' => $this->expectedSalaryMin,
                'expected_salary_max' => $this->expectedSalaryMax,
                'preferences' => $this->preferences,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
