<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use App\Enums\Concerns\StringifyEnum;

enum ActivityType: string
{
    use StringifyEnum;

    case Article = 'article';
    case PrMerged = 'pr_merged';
    case Mentoring = 'mentoring';
    case SquadProject = 'squad_project';
    case Referral = 'referral';
    case PeerReview = 'peer_review';
    case CallParticipation = 'call_participation';
    case ForumDebate = 'forum_debate';
    case ContentShare = 'content_share';
    case Engagement = 'engagement';
    case RepoStar = 'repo_star';
    case Message = 'message';
    case Voice = 'voice';
}
