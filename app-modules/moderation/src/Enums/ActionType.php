<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum ActionType: string
{
    case Warn = 'warn';
    case Mute = 'mute';
    case Kick = 'kick';
    case Ban = 'ban';
    case Suspend = 'suspend';
    case ContentRemove = 'content_remove';
}
