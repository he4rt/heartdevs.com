<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Casts;

use He4rt\Streaming\Streamer\Data\StreamerSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<StreamerSettings, StreamerSettings|array<array-key, mixed>>
 */
final class AsStreamerSettings implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): StreamerSettings
    {
        $payload = json_decode(is_string($value) ? $value : '{}', associative: true);

        return StreamerSettings::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @param  StreamerSettings|array<array-key, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $settings = match (true) {
            $value instanceof StreamerSettings => $value,
            is_array($value) => StreamerSettings::fromArray($value),
            default => new StreamerSettings,
        };

        return json_encode($settings->toArray(), JSON_THROW_ON_ERROR);
    }
}
