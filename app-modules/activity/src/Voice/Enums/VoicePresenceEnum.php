<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Enums;

enum VoicePresenceEnum: string
{
    case Joined = 'joined';
    case Left = 'left';
}
