<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Data;

use He4rt\Identity\Users\User;
use Spatie\LaravelData\Data;

final class UserRowData extends Data
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public array $roles,
        public bool $verified,
        public string $createdAt,
        public bool $canImpersonate = false,
    ) {}

    public static function fromModel(User $user, bool $canImpersonate = false): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            roles: $user->roles->pluck('name')->map(strval(...))->values()->all(),
            verified: $user->email_verified_at !== null,
            createdAt: $user->created_at->toIso8601String(),
            canImpersonate: $canImpersonate,
        );
    }
}
