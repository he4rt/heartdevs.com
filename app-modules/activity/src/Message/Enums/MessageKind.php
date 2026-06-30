<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Enums;

use App\Enums\Concerns\StringifyEnum;

enum MessageKind: string
{
    use StringifyEnum;

    case Default = 'default';
    case Reply = 'reply';
    case ThreadCreated = 'thread_created';
    case ThreadStarter = 'thread_starter';
    case Pin = 'pin';
    case UserJoin = 'user_join';
    case Boost = 'boost';
    case Command = 'command';
    case Call = 'call';
    case AutoModeration = 'auto_moderation';
    case ChannelUpdate = 'channel_update';
    case Poll = 'poll';
    case System = 'system';
    case Unknown = 'unknown';
}
