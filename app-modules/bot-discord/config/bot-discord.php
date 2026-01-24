<?php

declare(strict_types=1);

return [
    'channels' => [
        'auto-report' => env('HE4RT_AUTO_REPORT_CHANNEL_ID', '1045804587195576451'),
        'presentations' => env('HE4RT_PRESENTATIONS_CHANNEL_ID', '540993663468306433'),
    ],
    'roles' => [
        'presentation' => env('HE4RT_PRESENTATION_ROLE_ID', '546150872397119491'),

        // Tecnologias e especialidades disponíveis para seleção
        'technologies' => [
            'basic_english' => '546148708077666315',
            'intermediate_english' => '546148711416332298',
            'advanced_english' => '546148712833875985',
            'designer' => '547606728179449895',
            'ux_ui' => '546152565633449995',
            'javascript' => '540993488410378281',
            'php' => '540994118634176512',
            'python' => '540994295541399552',
            'java' => '540995379538165774',
            'c_c++_c#' => '541021498064896000',
            'rust' => '1043325810347606056',
            'ruby' => '540995627559944207',
            'perl' => '540995072246939648',
            'elixir' => '958788344974831687',
            'gamedev' => '550411669084569602',
            'he4rt_delas' => '1018009963903328308',
        ],
    ],
];
