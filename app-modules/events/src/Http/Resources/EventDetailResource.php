<?php

declare(strict_types=1);

namespace He4rt\Events\Http\Resources;

use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
final class EventDetailResource extends JsonResource
{
    public function __construct(
        Event $event,
        private readonly ?Enrollment $enrollment,
    ) {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $policy = $this->enrollmentPolicy;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'event_type' => $this->event_type->value,
            'location' => $this->location,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status->value,
            'enrollment_method' => $policy?->enrollment_method->value,
            'capacity' => $policy?->capacity,
            'has_waitlist' => $policy === null ? false : $policy->has_waitlist,
            'enrollment' => $this->enrollment instanceof Enrollment ? [
                'status' => $this->enrollment->status->value,
                'waitlist_position' => $this->enrollment->waitlist_position,
                'enrolled_at' => $this->enrollment->enrolled_at?->toIso8601String(),
                'confirmed_at' => $this->enrollment->confirmed_at?->toIso8601String(),
                'checked_in_at' => $this->enrollment->checked_in_at?->toIso8601String(),
            ] : null,
            'can_apply' => $this->canApply($policy),
            'can_confirm_presence' => $this->canConfirmPresence($policy),
            'is_event_full' => $this->isEventFull($policy),
        ];
    }

    private function canConfirmPresence(?EnrollmentPolicy $policy): bool
    {
        if ($this->status !== EventStatus::Published || $this->enrollment instanceof Enrollment) {
            return false;
        }

        if (!in_array($policy?->enrollment_method, [EnrollmentMethod::Rsvp, EnrollmentMethod::RsvpCheckin], strict: true)) {
            return false;
        }

        if (!$this->starts_at->isFuture()) {
            return false;
        }

        if ($policy->capacity === null) {
            return true;
        }

        return $this->occupiedCount() < $policy->capacity || $policy->has_waitlist;
    }

    private function canApply(?EnrollmentPolicy $policy): bool
    {
        if ($this->status !== EventStatus::Published || $this->enrollment instanceof Enrollment) {
            return false;
        }

        if ($policy?->enrollment_method !== EnrollmentMethod::Application) {
            return false;
        }

        return $this->starts_at->isFuture();
    }

    private function isEventFull(?EnrollmentPolicy $policy): bool
    {
        if ($this->enrollment instanceof Enrollment || $policy?->capacity === null || $policy->has_waitlist) {
            return false;
        }

        return $this->occupiedCount() >= $policy->capacity;
    }

    private function occupiedCount(): int
    {
        return Enrollment::query()
            ->where('event_id', $this->id)
            ->active()
            ->count();
    }
}
