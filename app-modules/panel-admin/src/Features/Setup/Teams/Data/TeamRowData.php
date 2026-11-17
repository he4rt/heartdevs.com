<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Data;

use App\Data\Support\EnumOptionData;
use He4rt\Identity\Teams\Team;
use Spatie\LaravelData\Data;

final class TeamRowData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public EnumOptionData $status,
        public string $ownerName,
        public int $membersCount,
        public string $createdAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            status: EnumOptionData::fromEnum($team->status),
            ownerName: $team->owner->name,
            membersCount: (int) $team->getAttribute('members_count'),
            createdAt: $team->created_at->toIso8601String(),
            deletedAt: $team->deleted_at?->toIso8601String(),
        );
    }
}
