<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Enums;

enum TradeDirection: string
{
    case Offer = 'offer';
    case Request = 'request';
}
