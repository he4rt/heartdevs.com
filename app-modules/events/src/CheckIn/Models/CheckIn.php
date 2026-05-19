<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Database\Factories\CheckInFactory;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $enrollment_id
 * @property Carbon $event_date
 * @property CheckInMethod $method
 * @property array<string, mixed>|null $payload
 * @property string|null $recorded_by
 * @property Carbon $created_at
 */
#[Table('events_check_ins')]
final class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function newFactory(): CheckInFactory
    {
        return CheckInFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'method' => CheckInMethod::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
