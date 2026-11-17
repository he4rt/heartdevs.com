<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Data;

use He4rt\Identity\Teams\Team;
use Spatie\LaravelData\Data;

final class FeatureFlagTeamRowData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public bool $enabled,
    ) {}

    public static function fromModel(Team $team): self
    {
        return new self(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            enabled: $team->getAttribute('feature_enabled') !== null,
        );
    }
}
