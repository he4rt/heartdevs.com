<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Discord',
        'cluster_breadcrumb' => 'Discord',
        'group_overview' => 'Overview',
        'group_server' => 'Server',
        'group_events' => 'Events',
        'dashboard' => 'Dashboard',
        'guilds' => 'Guilds',
        'channels' => 'Channels',
        'roles' => 'Roles',
        'members' => 'Members',
        'event_logs' => 'Event Logs',
    ],

    'guilds' => [
        'label' => 'Guild',
        'plural' => 'Guilds',
        'fields' => [
            'icon' => 'Icon',
            'name' => 'Name',
            'description' => 'Description',
            'discord_guild_id' => 'Guild ID',
            'member_count' => 'Members',
            'premium_tier' => 'Boost tier',
            'channels_count' => 'Channels',
            'roles_count' => 'Roles',
            'synced_at' => 'Last synced',
            'features' => 'Features',
        ],
        'sections' => [
            'overview' => 'Overview',
            'features' => 'Features',
        ],
    ],

    'channels' => [
        'label' => 'Channel',
        'plural' => 'Channels',
        'fields' => [
            'name' => 'Name',
            'type' => 'Type',
            'topic' => 'Topic',
            'category' => 'Category',
            'guild' => 'Guild',
            'position' => 'Position',
            'nsfw' => 'Age-restricted',
            'bitrate' => 'Bitrate',
            'user_limit' => 'User limit',
            'discord_channel_id' => 'Channel ID',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ],
        'filters' => [
            'type' => 'Type',
            'nsfw' => 'Age-restricted',
        ],
        'groups' => [
            'uncategorized' => 'Uncategorized',
        ],
        'sections' => [
            'channel' => 'Channel',
            'settings' => 'Settings',
        ],
    ],

    'members' => [
        'label' => 'Member',
        'plural' => 'Members',
        'fields' => [
            'avatar' => 'Avatar',
            'username' => 'Username',
            'global_name' => 'Display name',
            'nickname' => 'Nickname',
            'joined_at' => 'Joined at',
            'roles_count' => 'Roles',
            'is_bot' => 'Bot',
            'premium_since' => 'Boosting since',
            'left_at' => 'Left at',
            'discord_user_id' => 'User ID',
            'guild' => 'Guild',
            'is_pending' => 'Pending verification',
            'communication_disabled_until' => 'Timed out until',
            'roles' => 'Roles',
        ],
        'filters' => [
            'left' => [
                'label' => 'Server status',
                'true' => 'Left',
                'false' => 'Active',
                'placeholder' => 'All',
            ],
            'is_bot' => 'Bot',
            'is_pending' => 'Pending verification',
            'roles' => 'Roles',
        ],
        'sections' => [
            'profile' => 'Profile',
            'status' => 'Status',
            'roles' => 'Roles',
        ],
    ],

    'roles' => [
        'label' => 'Role',
        'plural' => 'Roles',
        'fields' => [
            'color' => 'Color',
            'name' => 'Name',
            'position' => 'Position',
            'members_count' => 'Members',
            'is_hoisted' => 'Shown separately',
            'is_mentionable' => 'Mentionable',
            'is_managed' => 'Managed by integration',
            'discord_role_id' => 'Role ID',
            'permissions' => 'Permissions',
            'permissions_helper' => 'Discord permissions bitfield',
            'guild' => 'Guild',
        ],
        'filters' => [
            'is_managed' => 'Managed by integration',
            'is_hoisted' => 'Shown separately',
        ],
        'sections' => [
            'role' => 'Role',
        ],
    ],

    'event_logs' => [
        'label' => 'Event Log',
        'plural' => 'Event Logs',
        'fields' => [
            'event_type' => 'Event type',
            'user_id' => 'User ID',
            'channel_id' => 'Channel ID',
            'guild_id' => 'Guild ID',
            'created_at' => 'Occurred at',
            'payload' => 'Payload',
        ],
        'filters' => [
            'event_type' => 'Event type',
            'period' => [
                'label' => 'Period',
                'from' => 'From',
                'until' => 'Until',
            ],
        ],
        'sections' => [
            'event' => 'Event',
            'payload' => 'Payload',
        ],
    ],

    'dashboard' => [
        'heading' => 'Discord Overview',
        'stats' => [
            'active_members' => 'Active members',
            'active_members_desc' => 'currently in the server',
            'joins_7d' => 'Joins (7d)',
            'joins_7d_desc' => 'last 7 days',
            'leaves_7d' => 'Leaves (7d)',
            'leaves_7d_desc' => 'last 7 days',
            'events_24h' => 'Events (24h)',
            'events_24h_desc' => 'last 24 hours',
            'boosters' => 'Boosters',
            'boosters_desc' => 'active boosts',
            'channels' => 'Channels',
            'channels_desc' => 'excluding categories',
        ],
        'events_per_day' => 'Events per Day',
        'events_per_day_label' => 'Events',
        'member_growth' => [
            'heading' => 'Member Growth',
            'joins' => 'Joins',
            'leaves' => 'Leaves',
        ],
    ],
];
