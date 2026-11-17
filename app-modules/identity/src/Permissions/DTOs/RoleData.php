<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\DTOs;

final readonly class RoleData
{
    /**
     * @param  array<int, string>  $permissionIds
     */
    public function __construct(
        public string $name,
        public array $permissionIds,
    ) {}

    /** @return array{name: string} */
    public function toDatabase(): array
    {
        return ['name' => $this->name];
    }
}
