<?php

declare(strict_types=1);

return [
    'invalid_check_in_status' => 'Only confirmed or checked-in enrollments can be checked in.',
    'check_in_outside_event_date_range' => 'Check-in date must be within the event date range.',
    'already_checked_in_for_date' => 'This enrollment has already checked in for this date.',
    'invalid_check_in_actor' => 'Manual check-in requires the organizer user id.',
    'qr_token_not_found' => 'QR token not found or does not belong to this event.',
    'qr_token_expired' => 'This QR token has expired.',
    'invalid_check_in_code' => 'Invalid check-in code.',
    'invalid_check_in_code_format' => 'Enter a 4 or 6 digit check-in code.',
    'check_in_code_expired' => 'Code has expired.',
    'check_in_code_exhausted' => 'Code has reached maximum uses.',
    'check_in_code_wrong_date' => 'Code is not valid for today.',
    'check_in_code_rate_limited' => 'Too many attempts. Try again in :seconds seconds.',
    'bot_user_not_linked' => 'Your account is not linked to this platform.',
    'bot_no_active_enrollment' => 'No active enrollment found for an event happening today.',
    'bot_multiple_active_events' => 'You have multiple active events today. Specify which event to check in.',
    'bot_check_in_success' => 'Check-in confirmed. See you there!',
];
