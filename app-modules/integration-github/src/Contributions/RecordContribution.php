<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Idempotent writer for contributions, shared by backfill (bulk, silent) and
 * webhook ingestion (live, emits the seam event). Convergence is guaranteed by
 * the unique (repo, type, external_ref) key.
 */
final class RecordContribution
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        string $repo,
        ContributionType $type,
        string $externalRef,
        string $actorLogin,
        ?int $actorId,
        string $occurredAt,
        ?string $targetRef,
        array $metadata,
        bool $emit = false,
    ): GithubContribution {
        $contribution = GithubContribution::query()->updateOrCreate(
            ['repo' => $repo, 'type' => $type, 'external_ref' => $externalRef],
            [
                'actor_login' => $actorLogin,
                'actor_id' => $actorId,
                'target_ref' => $targetRef,
                'occurred_at' => $occurredAt,
                'metadata' => $metadata,
            ],
        );

        if ($emit) {
            event(new GithubContributionRecorded($contribution));
        }

        return $contribution;
    }
}
