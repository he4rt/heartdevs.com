<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Data;

use App\Data\Support\EnumOptionData;
use He4rt\Identity\Teams\Team;
use He4rt\PanelAdmin\Features\Setup\Users\Data\UserOptionData;
use Spatie\LaravelData\Data;

final class TeamDetailData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $description,
        public string $contactEmail,
        public EnumOptionData $status,
        public UserOptionData $owner,
        public int $membersCount,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            description: $team->description,
            contactEmail: $team->contact_email,
            status: EnumOptionData::fromEnum($team->status),
            owner: UserOptionData::fromModel($team->owner),
            membersCount: $team->members()->count(),
            deletedAt: $team->deleted_at?->toIso8601String(),
        );
    }
}
