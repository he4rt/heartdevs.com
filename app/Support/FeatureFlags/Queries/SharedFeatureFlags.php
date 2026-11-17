<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags\Queries;

use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureRegistry;
use App\Support\FeatureFlags\FeatureScope;
use App\Support\FeatureFlags\FeatureState;
use App\Support\FeatureFlags\SharedWithFrontend;
use He4rt\Identity\Users\User;
use Laravel\Pennant\Feature;

/**
 * The state of the flags that the pages read, keyed by flag key. Only flags marked {@see SharedWithFrontend} leave the server.
 * A team flag waits until the page resolves a team.
 */
final readonly class SharedFeatureFlags
{
    public function __construct(private FeatureRegistry $registry) {}

    /**
     * @return array<string, bool>
     */
    public function for(User $user): array
    {
        $flags = collect($this->registry->all())
            ->filter(fn (FeatureFlag $flag): bool => $flag instanceof SharedWithFrontend && $flag->scope() !== FeatureScope::Team);

        if ($flags->isEmpty()) {
            return [];
        }

        $keys = $flags->map(fn (FeatureFlag $flag): string => $flag->key())->values()->all();
        Feature::for(scope: null)->load($keys);
        Feature::for($user)->load($keys);

        return $flags
            ->mapWithKeys(fn (FeatureFlag $flag): array => [
                $flag->key() => FeatureState::isActive($flag, $flag->scope() === FeatureScope::User ? $user : null),
            ])
            ->all();
    }
}
