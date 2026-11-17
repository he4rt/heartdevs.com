<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Collection;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

/**
 * An HMAC over the stored columns, to detect a row changed after it was written. It cannot detect a
 * deleted row. The payload and key match the hashes the Filament activity log plugin wrote, so old
 * rows still verify.
 */
final class ActivityIntegrity
{
    private const array SIGNED_COLUMNS = [
        'log_name', 'description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'event',
        'properties', 'attribute_changes', 'batch_uuid', 'risk_score', 'risk_level', 'retention_hold',
        'request_id', 'ip_address', 'created_at', 'updated_at',
    ];

    public function hash(Activity $activity): string
    {
        $payload = $this->canonicalize($this->signedValues($activity));

        return hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $this->key());
    }

    public function verify(Activity $activity): bool
    {
        $storedHash = $activity->getAttribute('integrity_hash');

        return is_string($storedHash) && $storedHash !== '' && hash_equals($storedHash, $this->hash($activity));
    }

    /**
     * A column the table does not have signs as null, as the plugin did outside strict mode.
     *
     * @return array<string, mixed>
     */
    private function signedValues(Activity $activity): array
    {
        $stored = $activity->getAttributes();
        $values = [];

        foreach ([$activity->getKeyName(), ...self::SIGNED_COLUMNS] as $column) {
            $values[$column] = array_key_exists($column, $stored) ? $activity->getAttribute($column) : null;
        }

        return $values;
    }

    private function key(): string
    {
        $configured = config('he4rt.activitylog.integrity_key');
        $key = is_string($configured) && $configured !== '' ? $configured : config()->string('app.key');

        throw_if($key === '', RuntimeException::class, 'Set ACTIVITY_LOG_INTEGRITY_KEY or APP_KEY to sign activities.');

        return $key;
    }

    private function canonicalize(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $items = collect($value);

        if (!array_is_list($value)) {
            $items = $items->sortKeys();
        }

        return $items->map(fn (mixed $item): mixed => $this->canonicalize($item))->all();
    }
}
