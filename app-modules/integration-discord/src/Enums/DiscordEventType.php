<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum DiscordEventType: string implements HasColor, HasIcon, HasLabel
{
    case Ready = 'READY';
    case Resumed = 'RESUMED';
    case PresenceUpdate = 'PRESENCE_UPDATE';
    case PresencesReplace = 'PRESENCES_REPLACE';
    case TypingStart = 'TYPING_START';
    case UserSettingsUpdate = 'USER_SETTINGS_UPDATE';
    case GuildMembersChunk = 'GUILD_MEMBERS_CHUNK';
    case InteractionCreate = 'INTERACTION_CREATE';
    case UserUpdate = 'USER_UPDATE';

    // Guild
    case GuildCreate = 'GUILD_CREATE';
    case GuildDelete = 'GUILD_DELETE';
    case GuildUpdate = 'GUILD_UPDATE';

    case GuildBanAdd = 'GUILD_BAN_ADD';
    case GuildBanRemove = 'GUILD_BAN_REMOVE';

    case GuildEmojisUpdate = 'GUILD_EMOJIS_UPDATE';
    case GuildStickersUpdate = 'GUILD_STICKERS_UPDATE';

    case GuildMemberAdd = 'GUILD_MEMBER_ADD';
    case GuildMemberRemove = 'GUILD_MEMBER_REMOVE';
    case GuildMemberUpdate = 'GUILD_MEMBER_UPDATE';

    case GuildRoleCreate = 'GUILD_ROLE_CREATE';
    case GuildRoleUpdate = 'GUILD_ROLE_UPDATE';
    case GuildRoleDelete = 'GUILD_ROLE_DELETE';

    case GuildScheduledEventCreate = 'GUILD_SCHEDULED_EVENT_CREATE';
    case GuildScheduledEventUpdate = 'GUILD_SCHEDULED_EVENT_UPDATE';
    case GuildScheduledEventDelete = 'GUILD_SCHEDULED_EVENT_DELETE';
    case GuildScheduledEventUserAdd = 'GUILD_SCHEDULED_EVENT_USER_ADD';
    case GuildScheduledEventUserRemove = 'GUILD_SCHEDULED_EVENT_USER_REMOVE';

    case GuildScheduledEventExceptionCreate = 'GUILD_SCHEDULED_EVENT_EXCEPTION_CREATE';
    case GuildScheduledEventExceptionUpdate = 'GUILD_SCHEDULED_EVENT_EXCEPTION_UPDATE';
    case GuildScheduledEventExceptionDelete = 'GUILD_SCHEDULED_EVENT_EXCEPTION_DELETE';

    case GuildIntegrationsUpdate = 'GUILD_INTEGRATIONS_UPDATE';
    case IntegrationCreate = 'INTEGRATION_CREATE';
    case IntegrationUpdate = 'INTEGRATION_UPDATE';
    case IntegrationDelete = 'INTEGRATION_DELETE';
    case WebhooksUpdate = 'WEBHOOKS_UPDATE';
    case ApplicationCommandPermissionsUpdate = 'APPLICATION_COMMAND_PERMISSIONS_UPDATE';

    case InviteCreate = 'INVITE_CREATE';
    case InviteDelete = 'INVITE_DELETE';

    case AutoModerationRuleCreate = 'AUTO_MODERATION_RULE_CREATE';
    case AutoModerationRuleUpdate = 'AUTO_MODERATION_RULE_UPDATE';
    case AutoModerationRuleDelete = 'AUTO_MODERATION_RULE_DELETE';
    case AutoModerationActionExecution = 'AUTO_MODERATION_ACTION_EXECUTION';

    case GuildAuditLogEntryCreate = 'GUILD_AUDIT_LOG_ENTRY_CREATE';

    case GuildSoundboardSoundCreate = 'GUILD_SOUNDBOARD_SOUND_CREATE';
    case GuildSoundboardSoundUpdate = 'GUILD_SOUNDBOARD_SOUND_UPDATE';
    case GuildSoundboardSoundDelete = 'GUILD_SOUNDBOARD_SOUND_DELETE';
    case GuildSoundboardSoundsUpdate = 'GUILD_SOUNDBOARD_SOUNDS_UPDATE';
    case SoundboardSounds = 'SOUNDBOARD_SOUNDS';

    // Channel
    case ChannelCreate = 'CHANNEL_CREATE';
    case ChannelDelete = 'CHANNEL_DELETE';
    case ChannelUpdate = 'CHANNEL_UPDATE';
    case ChannelPinsUpdate = 'CHANNEL_PINS_UPDATE';

    // Threads
    case ThreadCreate = 'THREAD_CREATE';
    case ThreadUpdate = 'THREAD_UPDATE';
    case ThreadDelete = 'THREAD_DELETE';
    case ThreadListSync = 'THREAD_LIST_SYNC';
    case ThreadMemberUpdate = 'THREAD_MEMBER_UPDATE';
    case ThreadMembersUpdate = 'THREAD_MEMBERS_UPDATE';

    // Voice
    case VoiceStateUpdate = 'VOICE_STATE_UPDATE';
    case VoiceServerUpdate = 'VOICE_SERVER_UPDATE';
    /** Sent in response to Request Channel Info (ephemeral channel data). */
    case ChannelInfo = 'CHANNEL_INFO';
    /** Sent when the voice channel status changes. */
    case VoiceChannelStatusUpdate = 'VOICE_CHANNEL_STATUS_UPDATE';
    /** Sent when the voice channel start time changes. */
    case VoiceChannelStartTimeUpdate = 'VOICE_CHANNEL_START_TIME_UPDATE';

    // Stage Instance
    case StageInstanceCreate = 'STAGE_INSTANCE_CREATE';
    case StageInstanceUpdate = 'STAGE_INSTANCE_UPDATE';
    case StageInstanceDelete = 'STAGE_INSTANCE_DELETE';

    // Messages
    case MessageCreate = 'MESSAGE_CREATE';
    case MessageDelete = 'MESSAGE_DELETE';
    case MessageUpdate = 'MESSAGE_UPDATE';
    case MessageDeleteBulk = 'MESSAGE_DELETE_BULK';
    case MessageReactionAdd = 'MESSAGE_REACTION_ADD';
    case MessageReactionRemove = 'MESSAGE_REACTION_REMOVE';
    case MessageReactionRemoveAll = 'MESSAGE_REACTION_REMOVE_ALL';
    case MessageReactionRemoveEmoji = 'MESSAGE_REACTION_REMOVE_EMOJI';
    case MessagePollVoteAdd = 'MESSAGE_POLL_VOTE_ADD';
    case MessagePollVoteRemove = 'MESSAGE_POLL_VOTE_REMOVE';

    // Entitlements
    case EntitlementCreate = 'ENTITLEMENT_CREATE';
    case EntitlementUpdate = 'ENTITLEMENT_UPDATE';
    case EntitlementDelete = 'ENTITLEMENT_DELETE';

    // Subscriptions
    case SubscriptionCreate = 'SUBSCRIPTION_CREATE';
    case SubscriptionUpdate = 'SUBSCRIPTION_UPDATE';
    case SubscriptionDelete = 'SUBSCRIPTION_DELETE';

    // Game Server
    case GameServerUpdate = 'GAME_SERVER_UPDATE';
    case GameServerDelete = 'GAME_SERVER_DELETE';

    public function getLabel(): string
    {
        return __('integration-discord::enums.discord_event_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            // Messages → info
            self::MessageCreate,
            self::MessageDelete,
            self::MessageUpdate,
            self::MessageDeleteBulk,
            self::MessageReactionAdd,
            self::MessageReactionRemove,
            self::MessageReactionRemoveAll,
            self::MessageReactionRemoveEmoji,
            self::MessagePollVoteAdd,
            self::MessagePollVoteRemove => 'info',

            // Guild members → success
            self::GuildMemberAdd,
            self::GuildMemberRemove,
            self::GuildMemberUpdate => 'success',

            // Bans / auto moderation / audit log → danger
            self::GuildBanAdd,
            self::GuildBanRemove,
            self::AutoModerationRuleCreate,
            self::AutoModerationRuleUpdate,
            self::AutoModerationRuleDelete,
            self::AutoModerationActionExecution,
            self::GuildAuditLogEntryCreate => 'danger',

            // Voice / stage → warning
            self::VoiceStateUpdate,
            self::VoiceServerUpdate,
            self::ChannelInfo,
            self::VoiceChannelStatusUpdate,
            self::VoiceChannelStartTimeUpdate,
            self::StageInstanceCreate,
            self::StageInstanceUpdate,
            self::StageInstanceDelete => 'warning',

            // Channels / threads → primary
            self::ChannelCreate,
            self::ChannelDelete,
            self::ChannelUpdate,
            self::ChannelPinsUpdate,
            self::ThreadCreate,
            self::ThreadUpdate,
            self::ThreadDelete,
            self::ThreadListSync,
            self::ThreadMemberUpdate,
            self::ThreadMembersUpdate => 'primary',

            // Everything else → gray
            self::Ready,
            self::Resumed,
            self::PresenceUpdate,
            self::PresencesReplace,
            self::TypingStart,
            self::UserSettingsUpdate,
            self::GuildMembersChunk,
            self::InteractionCreate,
            self::UserUpdate,
            self::GuildCreate,
            self::GuildDelete,
            self::GuildUpdate,
            self::GuildEmojisUpdate,
            self::GuildStickersUpdate,
            self::GuildRoleCreate,
            self::GuildRoleUpdate,
            self::GuildRoleDelete,
            self::GuildScheduledEventCreate,
            self::GuildScheduledEventUpdate,
            self::GuildScheduledEventDelete,
            self::GuildScheduledEventUserAdd,
            self::GuildScheduledEventUserRemove,
            self::GuildScheduledEventExceptionCreate,
            self::GuildScheduledEventExceptionUpdate,
            self::GuildScheduledEventExceptionDelete,
            self::GuildIntegrationsUpdate,
            self::IntegrationCreate,
            self::IntegrationUpdate,
            self::IntegrationDelete,
            self::WebhooksUpdate,
            self::ApplicationCommandPermissionsUpdate,
            self::InviteCreate,
            self::InviteDelete,
            self::GuildSoundboardSoundCreate,
            self::GuildSoundboardSoundUpdate,
            self::GuildSoundboardSoundDelete,
            self::GuildSoundboardSoundsUpdate,
            self::SoundboardSounds,
            self::EntitlementCreate,
            self::EntitlementUpdate,
            self::EntitlementDelete,
            self::SubscriptionCreate,
            self::SubscriptionUpdate,
            self::SubscriptionDelete,
            self::GameServerUpdate,
            self::GameServerDelete => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            // Messages → chat bubble
            self::MessageCreate,
            self::MessageDelete,
            self::MessageUpdate,
            self::MessageDeleteBulk,
            self::MessagePollVoteAdd,
            self::MessagePollVoteRemove => Heroicon::OutlinedChatBubbleLeft,

            // Reactions → face smile
            self::MessageReactionAdd,
            self::MessageReactionRemove,
            self::MessageReactionRemoveAll,
            self::MessageReactionRemoveEmoji => Heroicon::OutlinedFaceSmile,

            // Members → users
            self::GuildMemberAdd,
            self::GuildMemberRemove,
            self::GuildMemberUpdate,
            self::GuildMembersChunk => Heroicon::OutlinedUsers,

            // Bans / auto moderation / audit log → shield exclamation
            self::GuildBanAdd,
            self::GuildBanRemove,
            self::AutoModerationRuleCreate,
            self::AutoModerationRuleUpdate,
            self::AutoModerationRuleDelete,
            self::AutoModerationActionExecution,
            self::GuildAuditLogEntryCreate => Heroicon::OutlinedShieldExclamation,

            // Voice / stage → speaker wave
            self::VoiceStateUpdate,
            self::VoiceServerUpdate,
            self::ChannelInfo,
            self::VoiceChannelStatusUpdate,
            self::VoiceChannelStartTimeUpdate,
            self::StageInstanceCreate,
            self::StageInstanceUpdate,
            self::StageInstanceDelete => Heroicon::OutlinedSpeakerWave,

            // Channels / threads → hashtag
            self::ChannelCreate,
            self::ChannelDelete,
            self::ChannelUpdate,
            self::ChannelPinsUpdate,
            self::ThreadCreate,
            self::ThreadUpdate,
            self::ThreadDelete,
            self::ThreadListSync,
            self::ThreadMemberUpdate,
            self::ThreadMembersUpdate => Heroicon::OutlinedHashtag,

            // Roles → tag
            self::GuildRoleCreate,
            self::GuildRoleUpdate,
            self::GuildRoleDelete => Heroicon::OutlinedTag,

            // Invites → envelope
            self::InviteCreate,
            self::InviteDelete => Heroicon::OutlinedEnvelope,

            // Scheduled events → calendar
            self::GuildScheduledEventCreate,
            self::GuildScheduledEventUpdate,
            self::GuildScheduledEventDelete,
            self::GuildScheduledEventUserAdd,
            self::GuildScheduledEventUserRemove,
            self::GuildScheduledEventExceptionCreate,
            self::GuildScheduledEventExceptionUpdate,
            self::GuildScheduledEventExceptionDelete => Heroicon::OutlinedCalendar,

            // Generic / everything else → bolt
            self::Ready,
            self::Resumed,
            self::PresenceUpdate,
            self::PresencesReplace,
            self::TypingStart,
            self::UserSettingsUpdate,
            self::InteractionCreate,
            self::UserUpdate,
            self::GuildCreate,
            self::GuildDelete,
            self::GuildUpdate,
            self::GuildEmojisUpdate,
            self::GuildStickersUpdate,
            self::GuildIntegrationsUpdate,
            self::IntegrationCreate,
            self::IntegrationUpdate,
            self::IntegrationDelete,
            self::WebhooksUpdate,
            self::ApplicationCommandPermissionsUpdate,
            self::GuildSoundboardSoundCreate,
            self::GuildSoundboardSoundUpdate,
            self::GuildSoundboardSoundDelete,
            self::GuildSoundboardSoundsUpdate,
            self::SoundboardSounds,
            self::EntitlementCreate,
            self::EntitlementUpdate,
            self::EntitlementDelete,
            self::SubscriptionCreate,
            self::SubscriptionUpdate,
            self::SubscriptionDelete,
            self::GameServerUpdate,
            self::GameServerDelete => Heroicon::OutlinedBolt,
        };
    }
}
