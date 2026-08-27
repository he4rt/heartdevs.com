<?php

declare(strict_types=1);

return [
    'warn' => 'Você recebeu um aviso. Motivo: :reason',
    'suspend' => 'Sua conta foi suspensa até :until. Motivo: :reason',
    'ban' => 'Sua conta foi banida. Duração: :duration. Motivo: :reason',
    'content_remove' => 'Conteúdo removido. Motivo: :reason',

    'discord_dm' => [
        'title' => 'Ação de moderação aplicada',
        'footer' => 'Para contestar, entre em contato com a administração.',
        'default_reason' => 'Violação das regras da comunidade',
        'removed_message' => "**Mensagem removida:**\n> :text",
        'field_type' => 'Tipo',
        'field_duration' => 'Duração',
        'field_reason' => 'Motivo',
        'warn' => 'Você recebeu um aviso por mensagem inadequada.',
        'mute' => 'Você foi silenciado temporariamente.',
        'kick' => 'Você foi removido do servidor.',
        'ban' => 'Você foi banido do servidor.',
        'default' => 'Uma ação de moderação foi aplicada à sua conta.',
    ],
];
