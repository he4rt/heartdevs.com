<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Models;

use Carbon\Carbon;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Database\Factories\EnrollmentPolicyFactory;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $event_id
 * @property EnrollmentMethod $enrollment_method
 * @property CheckInMethod $check_in_method
 * @property int|null $capacity
 * @property bool $waitlist_enabled
 * @property AttendanceRequirement $attendance_requirement
 * @property int|null $minimum_days
 * @property int|null $cancellation_deadline_hours
 * @property int $xp_on_confirmed
 * @property int $xp_on_checked_in
 * @property int $xp_on_attended
 * @property array<string, mixed>|null $application_schema
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Table('events_enrollment_policies')]
final class EnrollmentPolicy extends Model
{
    /** @use HasFactory<EnrollmentPolicyFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function newFactory(): EnrollmentPolicyFactory
    {
        return EnrollmentPolicyFactory::new();
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'enrollment_method' => EnrollmentMethod::class,
            'check_in_method' => CheckInMethod::class,
            'attendance_requirement' => AttendanceRequirement::class,
            'waitlist_enabled' => 'boolean',
            'application_schema' => 'array',
        ];
    }
}
