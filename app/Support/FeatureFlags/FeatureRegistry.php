<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Support\FeatureFlags\Exceptions\DuplicateFeatureKey;

/**
 * Every flag of the product. Each module registers its own flags from its service provider.
 */
final class FeatureRegistry
{
    /** @var array<string, FeatureFlag> */
    private array $flags = [];

    /**
     * @param  array<int, class-string<FeatureFlag>>  $classes
     */
    public function register(array $classes): void
    {
        foreach ($classes as $class) {
            $flag = new $class;
            $registered = $this->flags[$flag->key()] ?? null;

            throw_if($registered !== null && $registered::class !== $class, DuplicateFeatureKey::of($flag->key()));

            $this->flags[$flag->key()] = $flag;
        }
    }

    /**
     * @return array<int, FeatureFlag>
     */
    public function all(): array
    {
        return collect($this->flags)->values()->all();
    }

    public function find(string $key): ?FeatureFlag
    {
        return $this->flags[$key] ?? null;
    }
}
