<?php

declare(strict_types=1);

namespace He4rt\Economy\Enums;

enum TransactionType: string
{
    case Reward = 'reward';
    case Transfer = 'transfer';
    case Purchase = 'purchase';
}
