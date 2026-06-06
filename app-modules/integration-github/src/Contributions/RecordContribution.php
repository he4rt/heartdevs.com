<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions;

use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;

/**
 * Idempotent writer for contributions, shared by backfill (bulk, silent) and
 * webhook ingestion (live, emits the seam event). Convergence is guaranteed by
 * the unique (tenant_id, repo, type, external_ref) key.
 */
final class RecordContribution
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        string $tenantId,
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
            ['tenant_id' => $tenantId, 'repo' => $repo, 'type' => $type, 'external_ref' => $externalRef],
            [
                'actor_login' => $actorLogin,
                'actor_id' => $actorId,
                'target_ref' => $targetRef,
                'occurred_at' => $occurredAt,
                'metadata' => $metadata,
            ],
        );

        // Só emite na criação. Webhooks de edição/replay reprocessam a mesma
        // contribuição (updateOrCreate atualiza a linha) e não devem re-disparar a
        // seam — evita recompensas duplicadas em listeners downstream.
        if ($emit && $contribution->wasRecentlyCreated) {
            event(new GithubContributionRecorded($contribution));
        }

        return $contribution;
    }
}
