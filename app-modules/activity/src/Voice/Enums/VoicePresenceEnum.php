<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Enums;

use App\Enums\Concerns\StringifyEnum;

enum VoicePresenceEnum: string
{
    use StringifyEnum;

    case Joined = 'joined';
    case Left = 'left';
}
