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
        public ?string $expectedSalary = null,
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
            expectedSalary: isset($data['expected_salary']) && $data['expected_salary'] !== ''
                ? (string) $data['expected_salary']
                : null,
            preferences: isset($data['preferences'])
                ? ($data['preferences'] instanceof WorkPreferences
                    ? $data['preferences']
                    : WorkPreferences::makeFromPayload((array) $data['preferences']))
                : null,
        );
    }
}
