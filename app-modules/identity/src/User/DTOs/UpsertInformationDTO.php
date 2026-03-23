<?php

declare(strict_types=1);

namespace He4rt\Identity\User\DTOs;

use He4rt\Identity\User\Models\User;

final class UpsertInformationDTO
{
    public function __construct(
        public User $user,
        public string $name,
        public string $nickname,
        public string $about,
        public ?string $linkedinUrl,
        public ?string $githubUrl,
        public ?string $birthdate,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            user: $data['user'],
            name: $data['name'],
            nickname: $data['nickname'],
            about: $data['about'],
            linkedinUrl: $data['linkedin_url'] ?? null,
            githubUrl: $data['github_url'] ?? null,
            birthdate: $data['birthdate'] ?? null,
        );
    }
}
