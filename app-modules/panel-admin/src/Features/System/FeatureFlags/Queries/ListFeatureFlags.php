<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\FeatureFlags\Queries;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\StoredFeatureFlag;
use He4rt\PanelAdmin\Features\System\FeatureFlags\Data\FeatureFlagData;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

final readonly class ListFeatureFlags
{
    public function __construct(private FeatureRegistry $registry) {}

    /**
     * @return array<int, FeatureFlagData>
     */
    public function handle(): array
    {
        $openGates = $this->openGates();
        $enabledCounts = $this->enabledCounts();

        return collect($this->registry->all())
            ->sortBy(fn (FeatureFlag $flag): string => $flag->label())
            ->map(fn (FeatureFlag $flag): FeatureFlagData => FeatureFlagData::fromFlag(
                $flag,
                $openGates->contains($flag->key()),
                $enabledCounts->get($flag->key(), 0),
            ))
            ->values()
            ->all();
    }

    public function find(FeatureFlag $flag): FeatureFlagData
    {
        return FeatureFlagData::fromFlag(
            $flag,
            $this->openGates()->contains($flag->key()),
            $this->enabledCounts()->get($flag->key(), 0),
        );
    }

    /**
     * @return Collection<int, string>
     */
    private function openGates(): Collection
    {
        return StoredFeatureFlag::query()
            ->where('scope', Feature::serializeScope(scope: null))
            ->where('value', 'true')
            ->get(['name'])
            ->map(fn (StoredFeatureFlag $row): string => $row->name)
            ->toBase();
    }

    /**
     * @return Collection<string, int>
     */
    private function enabledCounts(): Collection
    {
        return StoredFeatureFlag::query()
            ->select('name')
            ->selectRaw('count(*) as enabled_count')
            ->whereNot('scope', Feature::serializeScope(scope: null))
            ->where('value', 'true')
            ->groupBy('name')
            ->get()
            ->mapWithKeys(fn (StoredFeatureFlag $row): array => [$row->name => (int) $row->getAttribute('enabled_count')])
            ->toBase();
    }
}
