<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Models;

use Carbon\Carbon;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $event_id
 * @property Carbon $event_date
 * @property string $code
 * @property Carbon $starts_at
 * @property Carbon $expires_at
 * @property int|null $max_uses
 * @property int $uses_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('events_check_in_codes')]
final class CheckInCode extends Model
{
    use HasUuids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
