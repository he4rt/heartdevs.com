<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * One place for admin-driven persistence facts that Spatie's model-event
 * logging never sees on its own — relationship syncs (role permissions),
 * state transitions, feature toggles.
 *
 * Every entry stamps the causer and subject and logs under the subject's class,
 * matching the model-event `log_name` so manual and automatic entries group
 * together. The context tap (ip_address, request_id, …) still fires through the
 * subject's beforeActivityLogged hook.
 *
 * - changed() writes `attribute_changes`, which the package infolist reads to
 *   build the "Changes" tab. Use it for before/after diffs.
 * - event() writes `properties`. Use it for facts that are not a field diff
 *   (a state transition's from/to, a toggle's flag/scope/active).
 */
final class AuditActivity
{
    /**
     * @param  array<string, mixed>  $attributes  values after the change
     * @param  array<string, mixed>  $old  values before the change
     * @param  BackedEnum|string|null  $log  channel (log_name); null uses the config default
     */
    public static function changed(
        Model $subject,
        string $event,
        array $attributes,
        array $old = [],
        ?string $description = null,
        BackedEnum|string|null $log = null,
    ): ?Activity {
        return self::on($subject, $event, $log)
            ->withChanges(['attributes' => $attributes, 'old' => $old])
            ->log($description ?? $event);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  BackedEnum|string|null  $log  channel (log_name); null uses the config default
     */
    public static function event(
        Model $subject,
        string $event,
        array $properties = [],
        ?string $description = null,
        BackedEnum|string|null $log = null,
    ): ?Activity {
        return self::on($subject, $event, $log)
            ->withProperties($properties)
            ->log($description ?? $event);
    }

    private static function on(Model $subject, string $event, BackedEnum|string|null $log): ActivityLogger
    {
        return activity($log)
            ->causedBy(auth()->user())
            ->performedOn($subject)
            ->event($event);
    }
}
