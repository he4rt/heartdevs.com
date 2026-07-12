<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

use He4rt\Profile\Enums\SkillProficiency;

final readonly class ProfileSkillDTO
{
    public function __construct(
        public string $skillId,
        public SkillProficiency $proficiency,
        public ?int $yearsExperience = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $proficiency = $data['proficiency'] ?? null;

        return new self(
            skillId: (string) ($data['skill_id'] ?? ''),
            proficiency: $proficiency instanceof SkillProficiency
                ? $proficiency
                : SkillProficiency::from((string) $proficiency),
            yearsExperience: isset($data['years_experience']) && $data['years_experience'] !== ''
                ? (int) $data['years_experience']
                : null,
        );
    }
}
