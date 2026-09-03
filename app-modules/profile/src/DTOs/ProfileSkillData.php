<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class ProfileSkillData
{
    public function __construct(
        public string $name,
        public string $category,
        public string $proficiency,
        public ?int $yearsExperience = null,
    ) {}
}
