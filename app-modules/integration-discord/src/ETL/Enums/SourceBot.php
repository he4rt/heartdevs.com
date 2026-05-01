<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Enums;

enum SourceBot: string
{
    case Dyno = 'dyno';
    case Heartdevs = 'heartdevs';
}
