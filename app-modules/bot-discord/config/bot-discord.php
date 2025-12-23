<?php

declare(strict_types=1);

return [
    'channels' => [
        'auto-report' => env('HE4RT_AUTO_REPORT_CHANNEL_ID', '1045804587195576451'),
        'presentations' => env('HE4RT_PRESENTATIONS_CHANNEL_ID', '540993663468306433'),
    ],
    'roles' => [
        'presentation' => env('HE4RT_PRESENTATION_ROLE_ID', '546150872397119491'),
    ],
];
