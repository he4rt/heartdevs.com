<?php

declare(strict_types=1);

namespace He4rt\Profile\DTOs;

final readonly class ProfileCardData
{
    /**
     * @param  list<string>  $skills  Names only, already limited to what the card shows.
     */
    public function __construct(
        public string $name,
        public string $username,
        public string $url,
        public ?string $avatarUrl,
        public string $initials,
        public ?int $level,
        public ?string $role,
        public ?string $location,
        public array $skills,
        public int $remainingSkills,
        public bool $availableForProposals,
    ) {}
}
