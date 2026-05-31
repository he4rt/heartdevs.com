<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Enums;

enum DiscordChannelType: int
{
    case GuildText = 0;
    case Dm = 1;
    case GuildVoice = 2;
    case GroupDm = 3;
    case GuildCategory = 4;
    case GuildAnnouncement = 5;
    case AnnouncementThread = 10;
    case PublicThread = 11;
    case PrivateThread = 12;
    case GuildStageVoice = 13;
    case GuildForum = 15;
    case GuildMedia = 16;
}
