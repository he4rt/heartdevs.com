<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Casts;

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<StreamEventDetails|null, StreamEventDetails|null>
 */
final class AsStreamEventDetails implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?StreamEventDetails
    {
        $type = is_string($attributes['type'] ?? null) ? StreamEventType::tryFrom($attributes['type']) : null;
        $decoded = json_decode(is_string($value) ? $value : '{}', associative: true);
        $payload = is_array($decoded) ? $decoded : [];

        return match ($type) {
            StreamEventType::Sub => SubDetails::fromArray($payload),
            StreamEventType::GiftSub => GiftSubDetails::fromArray($payload),
            StreamEventType::Cheer => CheerDetails::fromArray($payload),
            StreamEventType::Raid => RaidDetails::fromArray($payload),
            StreamEventType::Follow, null => null,
        };
    }

    /**
     * @param  StreamEventDetails|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (!$value instanceof StreamEventDetails) {
            return null;
        }

        return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
    }
}
