<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

final class ActivityRedactor
{
    public const string REDACTED = '[REDACTED]';

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public static function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            $redacted[$key] = match (true) {
                is_string($key) && self::isSensitive($key) => self::REDACTED,
                is_array($value) => self::redact($value),
                default => $value,
            };
        }

        return $redacted;
    }

    public static function isSensitive(string $key): bool
    {
        $normalized = mb_strtolower(str_replace(['-', ' '], '_', $key));
        $fields = config()->array('he4rt.activitylog.redaction.fields');

        if (in_array($normalized, $fields, strict: true)) {
            return true;
        }

        foreach (config()->array('he4rt.activitylog.redaction.patterns') as $pattern) {
            if (preg_match((string) $pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }
}
