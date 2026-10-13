<?php

declare(strict_types=1);

namespace He4rt\Events\Http\Resources;

use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
final class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Enrollment|null $enrollment */
        $enrollment = $this->relationLoaded('enrollments') ? $this->enrollments->first() : null;

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
            'enrollment_status' => $enrollment?->status->value,
        ];
    }
}
