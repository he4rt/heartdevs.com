<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum CaseSource: string
{
    case UserReport = 'user_report';
    case AutoDetect = 'auto_detect';
    case RuleMatch = 'rule_match';
    case ManualFlag = 'manual_flag';
}
