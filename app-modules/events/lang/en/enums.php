<?php

declare(strict_types=1);

return [
    'event_type' => [
        'meetup' => 'Meetup',
        'workshop' => 'Workshop',
        'conference' => 'Conference',
    ],

    'enrollment_method' => [
        'rsvp' => 'RSVP',
        'rsvp_checkin' => 'RSVP + Check-in',
        'application' => 'Application',
    ],

    'attendance_requirement' => [
        'all_days' => 'All Days',
        'any_day' => 'Any Day',
        'minimum_days' => 'Minimum Days',
    ],

    'enrollment_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'waitlisted' => 'Waitlisted',
        'checked_in' => 'Checked In',
        'attended' => 'Attended',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
        'no_show' => 'No Show',
    ],

    'check_in_method' => [
        'manual' => 'Manual',
        'numeric_code' => 'Numeric Code',
        'qr_code' => 'QR Code',
    ],
];
