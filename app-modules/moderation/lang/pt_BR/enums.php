<?php

declare(strict_types=1);

return [
    'action_type' => [
        'warn' => 'Aviso',
        'mute' => 'Silenciamento',
        'kick' => 'Expulsão',
        'ban' => 'Banimento',
        'suspend' => 'Suspensão',
        'content_remove' => 'Remoção de Conteúdo',
    ],

    'appeal_status' => [
        'pending' => 'Pendente',
        'reviewing' => 'Em Análise',
        'upheld' => 'Mantida',
        'overturned' => 'Revertida',
    ],

    'case_source' => [
        'user_report' => 'Denúncia de Usuário',
        'auto_detect' => 'Detecção Automática',
        'rule_match' => 'Regra Ativada',
        'manual_flag' => 'Flag Manual',
    ],

    'case_status' => [
        'pending' => 'Pendente',
        'assigned' => 'Atribuído',
        'resolved' => 'Resolvido',
        'escalated' => 'Escalado',
        'dismissed' => 'Descartado',
    ],

    'platform' => [
        'discord' => 'Discord',
        'twitch' => 'Twitch',
        'github' => 'GitHub',
        'twitter' => 'Twitter/X',
        'web' => 'Plataforma Web',
    ],

    'severity' => [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ],

    'violation_type' => [
        'spam' => 'Spam',
        'toxicity' => 'Toxicidade',
        'harassment' => 'Assédio',
        'nsfw' => 'Conteúdo Impróprio',
        'raid' => 'Raid',
        'impersonation' => 'Personificação',
        'other' => 'Outro',
    ],
];
