<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;

return [
    'admins' => env('HE4RT_ADMINS_USERNAMES', 'danielhe4rt,kaster'),
    // Single-tenant deployment: the fixed tenant UUID used while the legacy
    // tenant_id columns still exist (dropped in a later phase). Sourced from
    // env so the former Discord-guild → tenant infrastructure mapping lives in
    // config instead of an ExternalIdentity(model_type='tenant') row.
    'tenant_id' => env('HE4RT_TENANT_ID'),
    'season' => [
        'id' => (int) env('HE4RT_SEASON_ID', 2),
        'minimum_level_for_retro' => env('HE4RT_SEASON_MIN_LEVEL', 3),
    ],
    'server_key' => env('HE4RT_BOT_SECRET', 'he4rt'),
    'discord' => [
        'token' => env('DISCORD_TOKEN'),
        'voice_xp_interval' => (int) env('HE4RT_DISCORD_VOICE_XP_INTERVAL', 1_200),
        'levelup_channel_id' => env('HE4RT_DISCORD_LEVELUP_CHANNEL', '552332704381927424'),
        'guild_id' => env('HE4RT_DISCORD_GUILD', '452926217558163456'),
        'moderation' => [
            'admin_role_ids' => explode(',', (string) env('HE4RT_DISCORD_MODERATION_ADMIN_ROLES', '547549573959385098,547543574091268118,547549400164073472')),
            'mod_role_ids' => explode(',', (string) env('HE4RT_DISCORD_MODERATION_MOD_ROLES', '547549463942791181')),
            'mod_channel_id' => env('HE4RT_DISCORD_MODERATION_CHANNEL', '1095115912820043829'),
        ],
    ],
    'channels' => [
        'commands' => '542840741588762637',
        'dynamic_voice_category' => env('HE4RT_DYNAMIC_VOICE_CATEGORY'),
    ],
    'seasons' => [
        [
            'name' => 'Legado 4Y',
            'description' => 'A primeira temporada que dura desde o inicio da He4rt Developers.',
            'starts_at' => Date::parse('2018-08-01'),
            'ends_at' => Date::parse('2022-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
        [
            'name' => 'He4rt Shippuden',
            'description' => 'Segunda temporada chegando foda memo dps nois acha um nome melhor pra isso aqui.',
            'starts_at' => Date::parse('2023-01-01'),
            'ends_at' => Date::parse('2023-12-31'),
            'messages_count' => 0,
            'participants_count' => 0,
            'meetings_count' => 0,
            'badges_count' => 0,
        ],
    ],
];
