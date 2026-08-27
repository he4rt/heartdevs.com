<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Enums;

use App\Enums\Concerns\StringifyEnum;

/**
 * Canonical membership signals. Providers contribute their own mappings via
 * MessageActivityAdapter::extractMembershipEvent() — unknown string values are
 * still persisted (string column + lazy cast) so new providers don't require
 * a migration.
 */
enum MembershipEventKind: string
{
    use StringifyEnum;

    case UserJoin = 'user_join';
    case Boost = 'boost';
    case BoostTier1 = 'boost_tier_1';
    case BoostTier2 = 'boost_tier_2';
    case BoostTier3 = 'boost_tier_3';
}
