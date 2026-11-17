<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use Spatie\Activitylog\Models\Activity;

/**
 * Before and after values. attribute_changes wins; older entries kept them in properties.
 */
final class ActivityChanges
{
    /** @return array<array-key, mixed> */
    public static function old(Activity $activity): array
    {
        return self::read($activity, 'old');
    }

    /** @return array<array-key, mixed> */
    public static function new(Activity $activity): array
    {
        return self::read($activity, 'attributes');
    }

    /** @return array<array-key, mixed> */
    private static function read(Activity $activity, string $key): array
    {
        $values = $activity->attribute_changes?->get($key) ?? $activity->properties?->get($key);

        return is_array($values) ? $values : [];
    }
}
