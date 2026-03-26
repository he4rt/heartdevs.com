<?php

declare(strict_types=1);

namespace He4rt\Gamification\Item\Enums;

enum AcquisitionMethod: string
{
    case Drop = 'drop';
    case Purchase = 'purchase';
    case Trade = 'trade';
    case Reward = 'reward';
}
