<?php

declare(strict_types=1);

return [
    'navigation' => [
        'cluster' => 'Discord',
        'cluster_breadcrumb' => 'Discord',
        'group_overview' => 'Visão geral',
        'group_server' => 'Servidor',
        'group_events' => 'Eventos',
        'dashboard' => 'Dashboard',
        'guilds' => 'Guilds',
        'channels' => 'Canais',
        'roles' => 'Cargos',
        'members' => 'Membros',
        'event_logs' => 'Logs de eventos',
    ],

    'guilds' => [
        'label' => 'Guild',
        'plural' => 'Guilds',
        'fields' => [
            'icon' => 'Ícone',
            'name' => 'Nome',
            'description' => 'Descrição',
            'discord_guild_id' => 'ID da guild',
            'member_count' => 'Membros',
            'premium_tier' => 'Nível de boost',
            'channels_count' => 'Canais',
            'roles_count' => 'Cargos',
            'synced_at' => 'Última sincronização',
            'features' => 'Recursos',
        ],
        'sections' => [
            'overview' => 'Visão geral',
            'features' => 'Recursos',
        ],
    ],

    'channels' => [
        'label' => 'Canal',
        'plural' => 'Canais',
        'fields' => [
            'name' => 'Nome',
            'type' => 'Tipo',
            'topic' => 'Tópico',
            'category' => 'Categoria',
            'guild' => 'Guild',
            'position' => 'Posição',
            'nsfw' => 'Conteúdo sensível',
            'bitrate' => 'Taxa de bits',
            'user_limit' => 'Limite de usuários',
            'discord_channel_id' => 'ID do canal',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ],
        'filters' => [
            'type' => 'Tipo',
            'nsfw' => 'Conteúdo sensível',
        ],
        'groups' => [
            'uncategorized' => 'Sem categoria',
        ],
        'sections' => [
            'channel' => 'Canal',
            'settings' => 'Configurações',
        ],
    ],

    'members' => [
        'label' => 'Membro',
        'plural' => 'Membros',
        'fields' => [
            'avatar' => 'Avatar',
            'username' => 'Usuário',
            'global_name' => 'Nome de exibição',
            'nickname' => 'Apelido',
            'joined_at' => 'Entrou em',
            'roles_count' => 'Cargos',
            'is_bot' => 'Bot',
            'premium_since' => 'Boost desde',
            'left_at' => 'Saiu em',
            'discord_user_id' => 'ID do usuário',
            'guild' => 'Guild',
            'is_pending' => 'Verificação pendente',
            'communication_disabled_until' => 'Silenciado até',
            'roles' => 'Cargos',
        ],
        'filters' => [
            'left' => [
                'label' => 'Status no servidor',
                'true' => 'Saíram',
                'false' => 'Ativos',
                'placeholder' => 'Todos',
            ],
            'is_bot' => 'Bot',
            'is_pending' => 'Verificação pendente',
            'roles' => 'Cargos',
        ],
        'sections' => [
            'profile' => 'Perfil',
            'status' => 'Status',
            'roles' => 'Cargos',
        ],
    ],

    'roles' => [
        'label' => 'Cargo',
        'plural' => 'Cargos',
        'fields' => [
            'color' => 'Cor',
            'name' => 'Nome',
            'position' => 'Posição',
            'members_count' => 'Membros',
            'is_hoisted' => 'Exibido separadamente',
            'is_mentionable' => 'Mencionável',
            'is_managed' => 'Gerenciado por integração',
            'discord_role_id' => 'ID do cargo',
            'permissions' => 'Permissões',
            'permissions_helper' => 'Bitfield de permissões do Discord',
            'guild' => 'Guild',
        ],
        'filters' => [
            'is_managed' => 'Gerenciado por integração',
            'is_hoisted' => 'Exibido separadamente',
        ],
        'sections' => [
            'role' => 'Cargo',
        ],
    ],

    'event_logs' => [
        'label' => 'Log de evento',
        'plural' => 'Logs de eventos',
        'fields' => [
            'event_type' => 'Tipo de evento',
            'user_id' => 'ID do usuário',
            'channel_id' => 'ID do canal',
            'guild_id' => 'ID da guild',
            'created_at' => 'Ocorrido em',
            'payload' => 'Payload',
        ],
        'filters' => [
            'event_type' => 'Tipo de evento',
            'period' => [
                'label' => 'Período',
                'from' => 'De',
                'until' => 'Até',
            ],
        ],
        'sections' => [
            'event' => 'Evento',
            'payload' => 'Payload',
        ],
    ],

    'dashboard' => [
        'heading' => 'Visão geral do Discord',
        'stats' => [
            'active_members' => 'Membros ativos',
            'active_members_desc' => 'atualmente no servidor',
            'joins_7d' => 'Entradas (7d)',
            'joins_7d_desc' => 'últimos 7 dias',
            'leaves_7d' => 'Saídas (7d)',
            'leaves_7d_desc' => 'últimos 7 dias',
            'events_24h' => 'Eventos (24h)',
            'events_24h_desc' => 'últimas 24 horas',
            'boosters' => 'Boosters',
            'boosters_desc' => 'boosts ativos',
            'channels' => 'Canais',
            'channels_desc' => 'excluindo categorias',
        ],
        'events_per_day' => 'Eventos por dia',
        'events_per_day_label' => 'Eventos',
        'member_growth' => [
            'heading' => 'Crescimento de membros',
            'joins' => 'Entradas',
            'leaves' => 'Saídas',
        ],
    ],
];
