<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Database\Factories\CheckInFactory;
use He4rt\Events\Enrollment\Models\Enrollment;
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
 * @property Carbon|null $checked_in_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table(name: 'events_check_ins')]
final class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'enrollment_id',
        'event_date',
        'method',
        'payload',
        'checked_in_at',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
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
            'checked_in_at' => 'datetime',
        ];
    }
}
