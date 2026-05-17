<?php

declare(strict_types=1);

namespace He4rt\Events\Event\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Database\Factories\EventFactory;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string|null $tenant_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property EventType $event_type
 * @property string|null $location
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('events')]
final class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasOne<EnrollmentPolicy, $this> */
    public function enrollmentPolicy(): HasOne
    {
        return $this->hasOne(EnrollmentPolicy::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<CheckInCode, $this> */
    public function checkInCodes(): HasMany
    {
        return $this->hasMany(CheckInCode::class);
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
