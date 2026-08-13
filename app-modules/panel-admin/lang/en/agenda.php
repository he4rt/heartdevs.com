<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Agenda',
        'cluster_breadcrumb' => 'Agenda',
        'back_to_admin' => 'Back to Admin',
        'group' => 'Agenda',
        'upcoming_events' => 'Upcoming Events',
    ],
    'resource' => [
        'label' => 'Event',
        'plural' => 'Events',
    ],
    'form' => [
        'title' => 'Title',
        'description' => 'Description',
        'category' => 'Category',
        'cover' => 'Cover image',
        'cover_hint' => 'Image shown on top of the event card on the landing page. Landscape format recommended.',
        'week_day' => 'Week day',
        'time' => 'Time',
        'event_at' => 'Event date',
        'location' => 'Location',
        'external_url' => 'External link',
        'is_active' => 'Show on landing',
        'skip_next_occurrence' => 'Skip next occurrence',
        'section_recurring' => 'Recurrence',
        'section_event' => 'Event details',
        'week_day_hint' => 'For weekly recurring events.',
        'time_hint' => 'Start time.',
        'event_at_hint' => 'For one-off events (e.g. pub meetup).',
        'skip_hint' => 'Hides only the next occurrence, without disabling the event.',
    ],
    'table' => [
        'next_occurrence' => 'Next occurrence',
    ],
    'weekdays' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ],
];
