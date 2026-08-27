<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Models;

use Carbon\Carbon;
use He4rt\Events\Database\Factories\EnrollmentTransitionFactory;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $enrollment_id
 * @property EnrollmentStatus|null $from_status
 * @property EnrollmentStatus $to_status
 * @property string|null $actor_id
 * @property TriggeredBy $triggered_by
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 */
#[Table(name: 'events_enrollment_transitions')]
final class EnrollmentTransition extends Model
{
    /** @use HasFactory<EnrollmentTransitionFactory> */
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'enrollment_id',
        'from_status',
        'to_status',
        'actor_id',
        'triggered_by',
        'reason',
        'metadata',
    ];

    /** @return BelongsTo<Enrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected static function newFactory(): EnrollmentTransitionFactory
    {
        return EnrollmentTransitionFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'from_status' => EnrollmentStatus::class,
            'to_status' => EnrollmentStatus::class,
            'triggered_by' => TriggeredBy::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
