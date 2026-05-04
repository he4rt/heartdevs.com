<?php

declare(strict_types=1);

return [
    'action_type' => [
        'warn' => 'Warning',
        'mute' => 'Mute',
        'kick' => 'Kick',
        'ban' => 'Ban',
        'suspend' => 'Suspension',
        'content_remove' => 'Content Removal',
    ],

    'appeal_status' => [
        'pending' => 'Pending',
        'reviewing' => 'Under Review',
        'upheld' => 'Upheld',
        'overturned' => 'Overturned',
    ],

    'case_source' => [
        'user_report' => 'User Report',
        'auto_detect' => 'Auto-Detected',
        'rule_match' => 'Rule Match',
        'manual_flag' => 'Manual Flag',
    ],

    'case_status' => [
        'pending' => 'Pending',
        'assigned' => 'Assigned',
        'resolved' => 'Resolved',
        'escalated' => 'Escalated',
        'dismissed' => 'Dismissed',
    ],

    'platform' => [
        'discord' => 'Discord',
        'twitch' => 'Twitch',
        'github' => 'GitHub',
        'twitter' => 'Twitter/X',
        'web' => 'Web Platform',
    ],

    'severity' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ],

    'violation_type' => [
        'spam' => 'Spam',
        'toxicity' => 'Toxicity',
        'harassment' => 'Harassment',
        'nsfw' => 'NSFW Content',
        'raid' => 'Raid',
        'impersonation' => 'Impersonation',
        'other' => 'Other',
    ],
];
