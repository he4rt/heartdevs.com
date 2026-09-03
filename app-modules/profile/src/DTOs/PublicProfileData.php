<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class PublicProfileData
{
    /**
     * @param  list<string>  $employmentTypes  Already translated labels, not enum values.
     * @param  list<ProfileLinkData>  $socialLinks
     * @param  list<ProfileLinkData>  $connectedAccounts
     * @param  list<ProfileSkillData>  $skills
     * @param  list<WorkExperienceData>  $experiences
     * @param  list<ProfileProjectData>  $projects
     * @param  list<ProfileBadgeData>  $badges
     */
    public function __construct(
        public string $name,
        public string $username,
        public ?string $avatarUrl,
        public string $initials,
        public ?string $coverUrl = null,
        public ?string $nickname = null,
        public ?string $headline = null,
        public ?string $currentPosition = null,
        public ?string $currentCompany = null,
        public bool $availableForProposals = false,
        public ?string $location = null,
        public ?string $about = null,
        public ?string $seniority = null,
        public ?int $yearsExperience = null,
        public ?string $startAvailability = null,
        public bool $openToRemote = false,
        public bool $willingToRelocate = false,
        public array $employmentTypes = [],
        public array $socialLinks = [],
        public array $connectedAccounts = [],
        public array $skills = [],
        public array $experiences = [],
        public array $projects = [],
        public ?int $level = null,
        public ?int $experience = null,
        public ?float $levelProgress = null,
        public ?int $experienceToNextLevel = null,
        public ?string $memberFor = null,
        public array $badges = [],
    ) {}
}
