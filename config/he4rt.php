<?php

declare(strict_types=1);

return [
    'activitylog' => [
        'integrity_key' => env('ACTIVITY_LOG_INTEGRITY_KEY'),
        'risk' => [
            'events' => ['deleted' => 45, 'force_deleted' => 70, 'restored' => 20, 'updated' => 10],
            'log_names' => ['security' => 35, 'auth' => 25, 'permissions' => 40, 'roles' => 40],
            'fields' => [
                '/password/i' => 45,
                '/token|secret|api_key|private_key/i' => 50,
                '/role|permission/i' => 35,
                '/email|phone|address/i' => 15,
            ],
            'context_score' => 5,
        ],
        'redaction' => [
            'fields' => [
                'password', 'password_confirmation', 'current_password', 'new_password', 'token', 'api_token',
                'access_token', 'refresh_token', 'secret', 'api_key', 'private_key', 'remember_token',
            ],
            'patterns' => ['/(^|_)(password|token|secret|key)$/'],
        ],
    ],
    'impersonation' => [
        'redirect_to' => env('IMPERSONATION_REDIRECT_TO', '/dashboard'),
    ],
];
