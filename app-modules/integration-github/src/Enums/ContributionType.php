<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Enums;

enum ContributionType: string
{
    case Pr = 'pr';
    case Review = 'review';
    case Issue = 'issue';
    case Comment = 'comment';
    case Commit = 'commit';
}
