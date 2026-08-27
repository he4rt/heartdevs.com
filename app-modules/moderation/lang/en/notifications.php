<?php

declare(strict_types=1);

return [
    'warn' => 'You received a warning. Reason: :reason',
    'suspend' => 'Your account has been suspended until :until. Reason: :reason',
    'ban' => 'Your account has been banned. Duration: :duration. Reason: :reason',
    'content_remove' => 'Content was removed. Reason: :reason',

    'discord_dm' => [
        'title' => 'Moderation action applied',
        'footer' => 'To appeal, please contact the administration.',
        'default_reason' => 'Violation of community rules',
        'removed_message' => "**Removed message:**\n> :text",
        'field_type' => 'Type',
        'field_duration' => 'Duration',
        'field_reason' => 'Reason',
        'warn' => 'You received a warning for an inappropriate message.',
        'mute' => 'You have been temporarily muted.',
        'kick' => 'You have been removed from the server.',
        'ban' => 'You have been banned from the server.',
        'default' => 'A moderation action has been applied to your account.',
    ],
];
