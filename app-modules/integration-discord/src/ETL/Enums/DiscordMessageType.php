<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Enums;

use He4rt\Activity\Message\Enums\MessageKind;

/**
 * Mirrors https://docs.discord.com/developers/resources/message#message-object-message-types.
 * Kept 1:1 with Discord API integers to preserve full fidelity; platform-agnostic
 * analysis uses MessageKind (via toCanonical()) instead.
 */
enum DiscordMessageType: int
{
    case Default = 0;
    case RecipientAdd = 1;
    case RecipientRemove = 2;
    case Call = 3;
    case ChannelNameChange = 4;
    case ChannelIconChange = 5;
    case ChannelPinnedMessage = 6;
    case UserJoin = 7;
    case GuildBoost = 8;
    case GuildBoostTier1 = 9;
    case GuildBoostTier2 = 10;
    case GuildBoostTier3 = 11;
    case ChannelFollowAdd = 12;
    case GuildDiscoveryDisqualified = 14;
    case GuildDiscoveryRequalified = 15;
    case GuildDiscoveryGraceInitial = 16;
    case GuildDiscoveryGraceFinal = 17;
    case ThreadCreated = 18;
    case Reply = 19;
    case ChatInputCommand = 20;
    case ThreadStarterMessage = 21;
    case GuildInviteReminder = 22;
    case ContextMenuCommand = 23;
    case AutoModerationAction = 24;
    case RoleSubscriptionPurchase = 25;
    case InteractionPremiumUpsell = 26;
    case StageStart = 27;
    case StageEnd = 28;
    case StageSpeaker = 29;
    case StageTopic = 31;
    case GuildApplicationPremiumSub = 32;
    case GuildIncidentAlertEnabled = 36;
    case GuildIncidentAlertDisabled = 37;
    case GuildIncidentReportRaid = 38;
    case GuildIncidentReportFalseAlarm = 39;
    case PurchaseNotification = 44;
    case PollResult = 46;

    public function toCanonical(): MessageKind
    {
        return match ($this) {
            self::Default => MessageKind::Default,
            self::Reply => MessageKind::Reply,
            self::ChannelPinnedMessage => MessageKind::Pin,
            self::UserJoin => MessageKind::UserJoin,
            self::GuildBoost,
            self::GuildBoostTier1,
            self::GuildBoostTier2,
            self::GuildBoostTier3 => MessageKind::Boost,
            self::ChatInputCommand,
            self::ContextMenuCommand => MessageKind::Command,
            self::ThreadCreated => MessageKind::ThreadCreated,
            self::ThreadStarterMessage => MessageKind::ThreadStarter,
            self::Call => MessageKind::Call,
            self::AutoModerationAction => MessageKind::AutoModeration,
            self::ChannelNameChange,
            self::ChannelIconChange,
            self::ChannelFollowAdd => MessageKind::ChannelUpdate,
            self::PollResult => MessageKind::Poll,
            self::RecipientAdd,
            self::RecipientRemove,
            self::GuildDiscoveryDisqualified,
            self::GuildDiscoveryRequalified,
            self::GuildDiscoveryGraceInitial,
            self::GuildDiscoveryGraceFinal,
            self::GuildInviteReminder,
            self::RoleSubscriptionPurchase,
            self::InteractionPremiumUpsell,
            self::StageStart,
            self::StageEnd,
            self::StageSpeaker,
            self::StageTopic,
            self::GuildApplicationPremiumSub,
            self::GuildIncidentAlertEnabled,
            self::GuildIncidentAlertDisabled,
            self::GuildIncidentReportRaid,
            self::GuildIncidentReportFalseAlarm,
            self::PurchaseNotification => MessageKind::System,
        };
    }
}
