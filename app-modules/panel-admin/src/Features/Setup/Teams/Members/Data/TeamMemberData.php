<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Members\Data;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;

final class TeamMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $isOwner,
        public ?string $joinedAt,
    ) {}

    public static function fromModel(User $user, Team $team): self
    {
        $joinedAt = $user->getAttribute('joined_at');

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            isOwner: $user->id === $team->owner_id,
            joinedAt: is_string($joinedAt) ? Date::parse($joinedAt)->toIso8601String() : null,
        );
    }
}
