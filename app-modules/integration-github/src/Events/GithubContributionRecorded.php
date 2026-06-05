<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Events;

use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Seam for downstream gamification: emitted when a live GitHub contribution is
 * recorded. A future listener (in activity/economy) can resolve the contributor's
 * Character via ExternalIdentity and award coins/xp. integration-github stays decoupled.
 */
final readonly class GithubContributionRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public GithubContribution $contribution,
    ) {}
}
