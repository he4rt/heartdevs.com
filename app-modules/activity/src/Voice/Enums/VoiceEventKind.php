<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Enums;

enum VoiceEventKind: string
{
    case Joined = 'joined';
    case Left = 'left';
}
