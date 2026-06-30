<?php

declare(strict_types=1);

namespace He4rt\Economy\Enums;

use App\Enums\Concerns\StringifyEnum;

enum TransactionType: string
{
    use StringifyEnum;

    case Reward = 'reward';
    case Transfer = 'transfer';
    case Purchase = 'purchase';
}
