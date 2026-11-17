<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Data;

use He4rt\Identity\Users\User;
use Spatie\LaravelData\Data;

final class FeatureFlagUserRowData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $enabled,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            enabled: $user->getAttribute('feature_enabled') !== null,
        );
    }
}
