<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Idempotent writer for contributions, shared by backfill (bulk, silent) and
 * webhook ingestion (live, emits the seam event). Convergence is guaranteed by
 * the unique (repo, type, external_ref) key.
 */
final class RecordContribution
{
    public function execute(NewContributionDTO $contribution, bool $emit = false): GithubContribution
    {
        $recorded = GithubContribution::query()->updateOrCreate(
            [
                'repo' => $contribution->repo,
                'type' => $contribution->type,
                'external_ref' => $contribution->externalRef,
            ],
            [
                'actor_login' => $contribution->actorLogin,
                'actor_id' => $contribution->actorId,
                'target_ref' => $contribution->targetRef,
                'occurred_at' => $contribution->occurredAt,
                'metadata' => $contribution->metadata,
            ],
        );

        // Só emite na criação. Webhooks de edição/replay reprocessam a mesma
        // contribuição (updateOrCreate atualiza a linha) e não devem re-disparar a
        // seam — evita recompensas duplicadas em listeners downstream.
        if ($emit && $recorded->wasRecentlyCreated) {
            event(new GithubContributionRecorded($recorded));
        }

        return $recorded;
    }
}
