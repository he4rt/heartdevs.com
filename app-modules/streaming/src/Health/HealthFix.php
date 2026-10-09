<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health;

enum HealthFix: string
{
    case Reconnect = 'reconnect';
    case RepairSubscriptions = 'repair_subscriptions';
}
