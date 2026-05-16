<?php

declare(strict_types=1);

namespace He4rt\Moderation\Platform;

use He4rt\Moderation\Enums\Platform;
use RuntimeException;

final class PlatformRegistry
{
    /** @var array<string, class-string<ModerationPlatformContract>> */
    private array $adapters = [];

    public function register(Platform $platform, string $adapterClass): void
    {
        $this->adapters[$platform->value] = $adapterClass;
    }

    public function resolve(Platform $platform): ModerationPlatformContract
    {
        $class = $this->adapters[$platform->value] ?? null;

        if ($class === null) {
            throw new RuntimeException('No adapter registered for platform: '.$platform->value);
        }

        return resolve($class);
    }

    public function has(Platform $platform): bool
    {
        return isset($this->adapters[$platform->value]);
    }
}
