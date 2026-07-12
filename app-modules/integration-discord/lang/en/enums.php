<?php

declare(strict_types=1);

return [
    'discord_event_type' => [
        'READY' => 'Gateway ready',
        'RESUMED' => 'Gateway resumed',
        'PRESENCE_UPDATE' => 'Presence updated',
        'PRESENCES_REPLACE' => 'Presences replaced',
        'TYPING_START' => 'Typing started',
        'USER_SETTINGS_UPDATE' => 'User settings updated',
        'GUILD_MEMBERS_CHUNK' => 'Guild members chunk received',
        'INTERACTION_CREATE' => 'Interaction created',
        'USER_UPDATE' => 'User updated',

        'GUILD_CREATE' => 'Guild created',
        'GUILD_DELETE' => 'Guild deleted',
        'GUILD_UPDATE' => 'Guild updated',

        'GUILD_BAN_ADD' => 'Member banned',
        'GUILD_BAN_REMOVE' => 'Member unbanned',

        'GUILD_EMOJIS_UPDATE' => 'Emojis updated',
        'GUILD_STICKERS_UPDATE' => 'Stickers updated',

        'GUILD_MEMBER_ADD' => 'Member joined',
        'GUILD_MEMBER_REMOVE' => 'Member left',
        'GUILD_MEMBER_UPDATE' => 'Member updated',

        'GUILD_ROLE_CREATE' => 'Role created',
        'GUILD_ROLE_UPDATE' => 'Role updated',
        'GUILD_ROLE_DELETE' => 'Role deleted',

        'GUILD_SCHEDULED_EVENT_CREATE' => 'Scheduled event created',
        'GUILD_SCHEDULED_EVENT_UPDATE' => 'Scheduled event updated',
        'GUILD_SCHEDULED_EVENT_DELETE' => 'Scheduled event deleted',
        'GUILD_SCHEDULED_EVENT_USER_ADD' => 'Scheduled event RSVP added',
        'GUILD_SCHEDULED_EVENT_USER_REMOVE' => 'Scheduled event RSVP removed',

        'GUILD_SCHEDULED_EVENT_EXCEPTION_CREATE' => 'Scheduled event exception created',
        'GUILD_SCHEDULED_EVENT_EXCEPTION_UPDATE' => 'Scheduled event exception updated',
        'GUILD_SCHEDULED_EVENT_EXCEPTION_DELETE' => 'Scheduled event exception deleted',

        'GUILD_INTEGRATIONS_UPDATE' => 'Guild integrations updated',
        'INTEGRATION_CREATE' => 'Integration created',
        'INTEGRATION_UPDATE' => 'Integration updated',
        'INTEGRATION_DELETE' => 'Integration deleted',
        'WEBHOOKS_UPDATE' => 'Webhooks updated',
        'APPLICATION_COMMAND_PERMISSIONS_UPDATE' => 'Application command permissions updated',

        'INVITE_CREATE' => 'Invite created',
        'INVITE_DELETE' => 'Invite deleted',

        'AUTO_MODERATION_RULE_CREATE' => 'Auto moderation rule created',
        'AUTO_MODERATION_RULE_UPDATE' => 'Auto moderation rule updated',
        'AUTO_MODERATION_RULE_DELETE' => 'Auto moderation rule deleted',
        'AUTO_MODERATION_ACTION_EXECUTION' => 'Auto moderation action executed',

        'GUILD_AUDIT_LOG_ENTRY_CREATE' => 'Audit log entry created',

        'GUILD_SOUNDBOARD_SOUND_CREATE' => 'Soundboard sound created',
        'GUILD_SOUNDBOARD_SOUND_UPDATE' => 'Soundboard sound updated',
        'GUILD_SOUNDBOARD_SOUND_DELETE' => 'Soundboard sound deleted',
        'GUILD_SOUNDBOARD_SOUNDS_UPDATE' => 'Soundboard sounds updated',
        'SOUNDBOARD_SOUNDS' => 'Soundboard sounds listed',

        'CHANNEL_CREATE' => 'Channel created',
        'CHANNEL_DELETE' => 'Channel deleted',
        'CHANNEL_UPDATE' => 'Channel updated',
        'CHANNEL_PINS_UPDATE' => 'Channel pins updated',

        'THREAD_CREATE' => 'Thread created',
        'THREAD_UPDATE' => 'Thread updated',
        'THREAD_DELETE' => 'Thread deleted',
        'THREAD_LIST_SYNC' => 'Thread list synced',
        'THREAD_MEMBER_UPDATE' => 'Thread member updated',
        'THREAD_MEMBERS_UPDATE' => 'Thread members updated',

        'VOICE_STATE_UPDATE' => 'Voice state updated',
        'VOICE_SERVER_UPDATE' => 'Voice server updated',
        'CHANNEL_INFO' => 'Channel info requested',
        'VOICE_CHANNEL_STATUS_UPDATE' => 'Voice channel status updated',
        'VOICE_CHANNEL_START_TIME_UPDATE' => 'Voice channel start time updated',

        'STAGE_INSTANCE_CREATE' => 'Stage instance created',
        'STAGE_INSTANCE_UPDATE' => 'Stage instance updated',
        'STAGE_INSTANCE_DELETE' => 'Stage instance deleted',

        'MESSAGE_CREATE' => 'Message sent',
        'MESSAGE_DELETE' => 'Message deleted',
        'MESSAGE_UPDATE' => 'Message edited',
        'MESSAGE_DELETE_BULK' => 'Messages bulk deleted',
        'MESSAGE_REACTION_ADD' => 'Reaction added',
        'MESSAGE_REACTION_REMOVE' => 'Reaction removed',
        'MESSAGE_REACTION_REMOVE_ALL' => 'All reactions removed',
        'MESSAGE_REACTION_REMOVE_EMOJI' => 'Reaction emoji removed',
        'MESSAGE_POLL_VOTE_ADD' => 'Poll vote added',
        'MESSAGE_POLL_VOTE_REMOVE' => 'Poll vote removed',

        'ENTITLEMENT_CREATE' => 'Entitlement created',
        'ENTITLEMENT_UPDATE' => 'Entitlement updated',
        'ENTITLEMENT_DELETE' => 'Entitlement deleted',

        'SUBSCRIPTION_CREATE' => 'Subscription created',
        'SUBSCRIPTION_UPDATE' => 'Subscription updated',
        'SUBSCRIPTION_DELETE' => 'Subscription deleted',

        'GAME_SERVER_UPDATE' => 'Game server updated',
        'GAME_SERVER_DELETE' => 'Game server deleted',
    ],
];
