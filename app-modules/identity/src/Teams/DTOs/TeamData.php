<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\DTOs;

use He4rt\Identity\Teams\TeamStatus;

final readonly class TeamData
{
    public function __construct(
        public string $name,
        public string $description,
        public TeamStatus $status,
        public string $contactEmail,
        public ?string $slug = null,
    ) {}

    /** @return array{name: string, description: string, status: TeamStatus, contact_email: string} */
    public function toDatabase(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'contact_email' => $this->contactEmail,
        ];
    }
}
