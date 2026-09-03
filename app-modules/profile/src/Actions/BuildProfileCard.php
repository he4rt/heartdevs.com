<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Profile\DTOs\ProfileCardData;
use He4rt\Profile\DTOs\ProfileSkillData;

final readonly class BuildProfileCard
{
    public const int MAX_SKILLS = 3;

    public function __construct(
        private BuildPublicProfile $buildPublicProfile,
    ) {}

    public function handle(User $user): ProfileCardData
    {
        $profile = $this->buildPublicProfile->handle($user);

        $skills = array_map(
            static fn (ProfileSkillData $skill): string => $skill->name,
            $profile->skills,
        );

        return new ProfileCardData(
            name: $profile->name,
            username: $profile->username,
            url: route('profile.public', $profile->username),
            avatarUrl: $profile->avatarUrl,
            initials: $profile->initials,
            level: $profile->level,
            role: $this->role($profile->headline, $profile->currentPosition, $profile->currentCompany),
            location: $profile->location,
            skills: array_slice($skills, 0, self::MAX_SKILLS),
            remainingSkills: max(0, count($skills) - self::MAX_SKILLS),
            availableForProposals: $profile->availableForProposals,
        );
    }

    private function role(?string $headline, ?string $position, ?string $company): ?string
    {
        if (filled($headline)) {
            return $headline;
        }

        $parts = array_filter([$position, $company], filled(...));

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
