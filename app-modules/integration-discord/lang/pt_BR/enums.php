<?php

declare(strict_types=1);

return [
    'discord_event_type' => [
        'READY' => 'Gateway pronto',
        'RESUMED' => 'Gateway retomado',
        'PRESENCE_UPDATE' => 'Presença atualizada',
        'PRESENCES_REPLACE' => 'Presenças substituídas',
        'TYPING_START' => 'Digitação iniciada',
        'USER_SETTINGS_UPDATE' => 'Configurações do usuário atualizadas',
        'GUILD_MEMBERS_CHUNK' => 'Lote de membros recebido',
        'INTERACTION_CREATE' => 'Interação criada',
        'USER_UPDATE' => 'Usuário atualizado',

        'GUILD_CREATE' => 'Servidor criado',
        'GUILD_DELETE' => 'Servidor removido',
        'GUILD_UPDATE' => 'Servidor atualizado',

        'GUILD_BAN_ADD' => 'Membro banido',
        'GUILD_BAN_REMOVE' => 'Banimento removido',

        'GUILD_EMOJIS_UPDATE' => 'Emojis atualizados',
        'GUILD_STICKERS_UPDATE' => 'Figurinhas atualizadas',

        'GUILD_MEMBER_ADD' => 'Membro entrou',
        'GUILD_MEMBER_REMOVE' => 'Membro saiu',
        'GUILD_MEMBER_UPDATE' => 'Membro atualizado',

        'GUILD_ROLE_CREATE' => 'Cargo criado',
        'GUILD_ROLE_UPDATE' => 'Cargo atualizado',
        'GUILD_ROLE_DELETE' => 'Cargo removido',

        'GUILD_SCHEDULED_EVENT_CREATE' => 'Evento agendado criado',
        'GUILD_SCHEDULED_EVENT_UPDATE' => 'Evento agendado atualizado',
        'GUILD_SCHEDULED_EVENT_DELETE' => 'Evento agendado removido',
        'GUILD_SCHEDULED_EVENT_USER_ADD' => 'Confirmação de presença adicionada',
        'GUILD_SCHEDULED_EVENT_USER_REMOVE' => 'Confirmação de presença removida',

        'GUILD_SCHEDULED_EVENT_EXCEPTION_CREATE' => 'Exceção de evento agendado criada',
        'GUILD_SCHEDULED_EVENT_EXCEPTION_UPDATE' => 'Exceção de evento agendado atualizada',
        'GUILD_SCHEDULED_EVENT_EXCEPTION_DELETE' => 'Exceção de evento agendado removida',

        'GUILD_INTEGRATIONS_UPDATE' => 'Integrações do servidor atualizadas',
        'INTEGRATION_CREATE' => 'Integração criada',
        'INTEGRATION_UPDATE' => 'Integração atualizada',
        'INTEGRATION_DELETE' => 'Integração removida',
        'WEBHOOKS_UPDATE' => 'Webhooks atualizados',
        'APPLICATION_COMMAND_PERMISSIONS_UPDATE' => 'Permissões de comando atualizadas',

        'INVITE_CREATE' => 'Convite criado',
        'INVITE_DELETE' => 'Convite removido',

        'AUTO_MODERATION_RULE_CREATE' => 'Regra de automoderação criada',
        'AUTO_MODERATION_RULE_UPDATE' => 'Regra de automoderação atualizada',
        'AUTO_MODERATION_RULE_DELETE' => 'Regra de automoderação removida',
        'AUTO_MODERATION_ACTION_EXECUTION' => 'Ação de automoderação executada',

        'GUILD_AUDIT_LOG_ENTRY_CREATE' => 'Entrada de auditoria criada',

        'GUILD_SOUNDBOARD_SOUND_CREATE' => 'Som do soundboard criado',
        'GUILD_SOUNDBOARD_SOUND_UPDATE' => 'Som do soundboard atualizado',
        'GUILD_SOUNDBOARD_SOUND_DELETE' => 'Som do soundboard removido',
        'GUILD_SOUNDBOARD_SOUNDS_UPDATE' => 'Sons do soundboard atualizados',
        'SOUNDBOARD_SOUNDS' => 'Sons do soundboard listados',

        'CHANNEL_CREATE' => 'Canal criado',
        'CHANNEL_DELETE' => 'Canal removido',
        'CHANNEL_UPDATE' => 'Canal atualizado',
        'CHANNEL_PINS_UPDATE' => 'Fixados do canal atualizados',

        'THREAD_CREATE' => 'Thread criada',
        'THREAD_UPDATE' => 'Thread atualizada',
        'THREAD_DELETE' => 'Thread removida',
        'THREAD_LIST_SYNC' => 'Lista de threads sincronizada',
        'THREAD_MEMBER_UPDATE' => 'Membro da thread atualizado',
        'THREAD_MEMBERS_UPDATE' => 'Membros da thread atualizados',

        'VOICE_STATE_UPDATE' => 'Estado de voz atualizado',
        'VOICE_SERVER_UPDATE' => 'Servidor de voz atualizado',
        'CHANNEL_INFO' => 'Informações do canal solicitadas',
        'VOICE_CHANNEL_STATUS_UPDATE' => 'Status do canal de voz atualizado',
        'VOICE_CHANNEL_START_TIME_UPDATE' => 'Horário de início do canal de voz atualizado',

        'STAGE_INSTANCE_CREATE' => 'Palco criado',
        'STAGE_INSTANCE_UPDATE' => 'Palco atualizado',
        'STAGE_INSTANCE_DELETE' => 'Palco removido',

        'MESSAGE_CREATE' => 'Mensagem enviada',
        'MESSAGE_DELETE' => 'Mensagem removida',
        'MESSAGE_UPDATE' => 'Mensagem editada',
        'MESSAGE_DELETE_BULK' => 'Mensagens removidas em lote',
        'MESSAGE_REACTION_ADD' => 'Reação adicionada',
        'MESSAGE_REACTION_REMOVE' => 'Reação removida',
        'MESSAGE_REACTION_REMOVE_ALL' => 'Todas as reações removidas',
        'MESSAGE_REACTION_REMOVE_EMOJI' => 'Emoji de reação removido',
        'MESSAGE_POLL_VOTE_ADD' => 'Voto em enquete adicionado',
        'MESSAGE_POLL_VOTE_REMOVE' => 'Voto em enquete removido',

        'ENTITLEMENT_CREATE' => 'Direito criado',
        'ENTITLEMENT_UPDATE' => 'Direito atualizado',
        'ENTITLEMENT_DELETE' => 'Direito removido',

        'SUBSCRIPTION_CREATE' => 'Assinatura criada',
        'SUBSCRIPTION_UPDATE' => 'Assinatura atualizada',
        'SUBSCRIPTION_DELETE' => 'Assinatura removida',

        'GAME_SERVER_UPDATE' => 'Servidor de jogo atualizado',
        'GAME_SERVER_DELETE' => 'Servidor de jogo removido',
    ],
];
