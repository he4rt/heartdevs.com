<?php

declare(strict_types=1);

namespace He4rt\User\DTO;

use He4rt\Provider\Enums\ProviderEnum;

readonly class UpdateProfileDTO
{
    public function __construct(
        public string $providerId,
        public ProviderEnum $provider,
        public int $tenantId,
        public ?string $name = null,
        public ?string $nickname = null,
        public ?string $linkedinUrl = null,
        public ?string $githubUrl = null,
        public ?string $birthdate = null,
        public ?string $about = null,
    ) {}

    public static function fromPayload(array $payload): self
    {
        return new self(
            providerId: $payload['provider_id'],
            provider: $payload['provider'],
            tenantId: $payload['tenant_id'],
            name: $payload['name'],
            nickname: $payload['nickname'],
            linkedinUrl: $payload['linkedin_url'],
            githubUrl: $payload['github_url'],
            birthdate: $payload['birthdate'],
            about: $payload['about']
        );
    }

    public function toProfile(): array
    {
        return [
            'name' => $this->name,
            'nickname' => $this->nickname,
            'linkedin_url' => $this->linkedinUrl,
            'github_url' => $this->githubUrl,
            'birthdate' => $this->birthdate,
            'about' => $this->about,
        ];
    }
}
