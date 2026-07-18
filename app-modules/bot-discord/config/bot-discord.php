<?php

declare(strict_types=1);

return [
    'guild_id' => env('HE4RT_DISCORD_GUILD_ID'),
    'channels' => [
        'auto-report' => env('HE4RT_AUTO_REPORT_CHANNEL_ID', '1045804587195576451'),
        'presentations' => env('HE4RT_PRESENTATIONS_CHANNEL_ID', '540993663468306433'),
        'general' => env('HE4RT_GERAL_CHANNEL_ID', '541443469642563585'),
        'he4rt_delas' => env('HE4RT_DELAS_CHANNEL_ID', '1450492362416586845'),
    ],
    'roles' => [
        'presentation' => env('HE4RT_PRESENTATION_ROLE_ID', '546150872397119491'),
        'comite_delas' => env('HE4RT_COMITE_DELAS_ROLE_ID', '1014311739397001288'),
        'he4rt_delas' => env('HE4RT_DELAS_ROLE_ID', '1018009963903328308'),
    ],
];
