<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use Spatie\Activitylog\Models\Activity;

/**
 * A 0-100 score from the event, the log name, the changed fields and the request context.
 * The weights live in config/he4rt.php.
 */
final class ActivityRisk
{
    private const int MAX_SCORE = 100;

    public static function score(Activity $activity): int
    {
        $score = self::weight('events', (string) $activity->event)
            + self::weight('log_names', (string) $activity->log_name);

        foreach (array_keys(ActivityChanges::new($activity) + ActivityChanges::old($activity)) as $field) {
            $score += self::fieldWeight((string) $field);
        }

        $hasIpAddress = filled($activity->properties?->get('ip_address'));

        if ($hasIpAddress) {
            $score += config()->integer('he4rt.activitylog.risk.context_score');
        }

        return min($score, self::MAX_SCORE);
    }

    public static function level(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 55 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }

    private static function weight(string $group, string $key): int
    {
        $weights = config()->array('he4rt.activitylog.risk.'.$group);

        return is_numeric($weights[$key] ?? null) ? (int) $weights[$key] : 0;
    }

    private static function fieldWeight(string $field): int
    {
        foreach (config()->array('he4rt.activitylog.risk.fields') as $pattern => $weight) {
            if (preg_match((string) $pattern, $field) === 1) {
                return is_numeric($weight) ? (int) $weight : 0;
            }
        }

        return 0;
    }
}
