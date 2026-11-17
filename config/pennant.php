<?php

declare(strict_types=1);

return [

    'default' => 'database',

    'stores' => [

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'stored-only',
            'connection' => null,
            'table' => 'feature_flags',
        ],

    ],
];
