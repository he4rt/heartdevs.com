<?php

declare(strict_types=1);

return [
    'pipeline' => [
        'sync' => env('MODERATION_PIPELINE_SYNC', default: true),
        'queue' => env('MODERATION_QUEUE', 'moderation'),
    ],

    'classifiers' => [
        'openai' => [
            'enabled' => env('MODERATION_OPENAI_ENABLED', default: true),
            'model' => 'omni-moderation-latest',
        ],
        'rules' => [
            'enabled' => true,
        ],
    ],

    'thresholds' => [
        'flag' => 0.7,
        'high_priority' => 0.9,
        'dismiss' => 0.3,
    ],

    'penalties' => [
        'escalation_window_days' => 30,
    ],

    'appeals' => [
        'sla_hours' => 48,
        'window_days' => 7,
    ],
];
