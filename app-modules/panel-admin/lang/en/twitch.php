<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Twitch',
        'cluster_breadcrumb' => 'Twitch',
        'back_to_admin' => 'Back to Admin',
        'group_overview' => 'Overview',
        'group_events' => 'Events',
        'dashboard' => 'Dashboard',
        'event_logs' => 'Event Logs',
        'subscriptions' => 'Subscriptions',
    ],

    'event_logs' => [
        'label' => 'Event Log',
        'plural' => 'Event Logs',
    ],

    'connect' => [
        'connect' => 'Connect Twitch',
        'reconnect' => 'Reconnect Twitch (:login)',
    ],

    'subscriptions' => [
        'label' => 'Subscription',
        'plural' => 'Subscriptions',
        'actions' => [
            'register' => 'Register Subscriptions',
            'register_confirm_button' => 'Register All',
            'registered' => 'Subscriptions registered successfully.',
            'register_failed' => 'Failed to register subscriptions.',
            'sync' => 'Sync from Twitch',
            'synced' => 'Subscriptions synced successfully.',
            'sync_failed' => 'Failed to sync subscriptions from Twitch.',
            'delete' => 'Delete',
            'deleted' => 'Subscription deleted.',
            'delete_failed' => 'Failed to delete subscription from Twitch.',
        ],
    ],

    'dashboard' => [
        'heading' => 'Twitch Overview',
        'stats' => [
            'total_events' => 'Total Events',
            'total_events_desc' => 'all time',
            'events_today' => 'Events Today',
            'events_today_desc' => 'last 24 hours',
            'active_subs' => 'Active Subscriptions',
            'active_subs_desc' => 'enabled',
            'error_subs' => 'Errored Subscriptions',
            'error_subs_desc' => 'need attention',
        ],
        'events_per_day' => 'Events per Day',
        'events_by_type' => 'Events by Type',
    ],
];
