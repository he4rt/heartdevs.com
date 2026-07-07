<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emitted when a GitHub pull request review is approved for a repository in the
 * allowlist. Downstream listeners can resolve the PR author through
 * ExternalIdentity and award review-related coins/xp without coupling
 * integration-github to activity/economy concerns.
 */
final readonly class GithubPullRequestApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $author_login,
        public string $repo,
        public int $pr_number,
        public string $approved_at,
    ) {}
}
