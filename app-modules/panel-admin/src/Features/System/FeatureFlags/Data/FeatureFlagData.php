<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Data;

use App\Data\Support\EnumOptionData;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\SharedWithFrontend;
use Spatie\LaravelData\Data;

final class FeatureFlagData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public EnumOptionData $scope,
        public bool $gateOpen,
        public int $enabledCount,
        public bool $sharedWithFrontend,
    ) {}

    public static function fromFlag(FeatureFlag $flag, bool $gateOpen, int $enabledCount): self
    {
        return new self(
            key: $flag->key(),
            label: $flag->label(),
            description: $flag->description(),
            scope: EnumOptionData::fromEnum($flag->scope()),
            gateOpen: $gateOpen,
            enabledCount: $enabledCount,
            sharedWithFrontend: $flag instanceof SharedWithFrontend,
        );
    }
}
