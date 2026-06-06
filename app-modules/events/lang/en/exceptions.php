<?php

declare(strict_types=1);

return [
    'invalid_transition' => 'Cannot transition enrollment from :from to :to.',
    'already_enrolled' => 'You are already enrolled in this event.',
    'event_past' => 'This event has already started and is no longer accepting enrollments.',
    'event_not_active' => 'This event is not available for enrollment.',
    'invalid_enrollment_method' => 'This event requires application enrollment, not RSVP.',
    'event_full' => 'This event has reached maximum capacity and does not have a waitlist.',
    'response_message_not_implemented' => 'Response message is not implemented for enrollment status: :status.',
    'override_reason_required' => 'A reason is required when overriding an enrollment status.',
    'override_not_allowed' => 'Override from :from to :to is not allowed.',
    'override_status_changed' => 'Enrollment status changed from :expected to :actual before the override was saved. Review the current status and try again.',
];
