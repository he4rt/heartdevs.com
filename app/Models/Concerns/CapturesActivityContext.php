<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Activitylog\ActivityContext;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Bundles the Spatie activity-logging setup for a model:
 *
 * - composes HasActivity (log its own events + act as a causer);
 * - logs every attribute except the ones listed in $hidden (secrets stay out
 *   of the audit trail — redaction is display-only and cannot protect the DB,
 *   exports or backups);
 * - stamps every logged event with the request context through
 *   {@see ActivityContext}. Spatie v5 no longer reads
 *   config('activitylog.activity_logger_taps'), so the model hook calls it.
 */
trait CapturesActivityContext
{
    use HasActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(static::class)
            ->logAll()
            ->logExcept($this->hidden);
    }

    public function beforeActivityLogged(Activity $activity, string $event): void
    {
        resolve(ActivityContext::class)->apply($activity);
    }
}
