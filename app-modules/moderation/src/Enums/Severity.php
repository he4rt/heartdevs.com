<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum Severity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
