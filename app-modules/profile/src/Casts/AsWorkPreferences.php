<?php

declare(strict_types=1);

namespace He4rt\Profile\Casts;

use He4rt\Profile\Data\WorkPreferences;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<WorkPreferences, WorkPreferences|array<string, mixed>>
 */
final class AsWorkPreferences implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): WorkPreferences
    {
        $payload = json_decode((string) ($value ?? '{}'), associative: true);

        return WorkPreferences::makeFromPayload(is_array($payload) ? $payload : []);
    }

    /**
     * @param  WorkPreferences|array<string, mixed>|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $preferences = match (true) {
            $value instanceof WorkPreferences => $value,
            is_array($value) => WorkPreferences::makeFromPayload($value),
            default => new WorkPreferences(),
        };

        return json_encode($preferences->toArray(), JSON_THROW_ON_ERROR);
    }
}
