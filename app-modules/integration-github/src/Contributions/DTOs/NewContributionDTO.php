<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Contributions\DTOs;

use He4rt\IntegrationGithub\Enums\ContributionType;

/**
 * Forma normalizada de uma contribuição, montada pelos ingestores (backfill e
 * webhook) e entregue ao RecordContribution. O external_ref é construído no call
 * site via ContributionType::ref().
 */
final readonly class NewContributionDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $tenantId,
        public string $repo,
        public ContributionType $type,
        public string $externalRef,
        public string $actorLogin,
        public ?int $actorId,
        public string $occurredAt,
        public ?string $targetRef,
        public array $metadata,
    ) {}
}
