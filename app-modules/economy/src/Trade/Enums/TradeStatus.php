<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Enums;

enum TradeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
