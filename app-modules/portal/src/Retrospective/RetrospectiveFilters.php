<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use Carbon\CarbonInterface;
use He4rt\IntegrationGithub\Enums\ContributionType;

/**
 * Filtros do read model da retrospectiva. Imutável; normaliza entradas cruas
 * (vindas da URL) em tipos seguros, aplicando defaults.
 */
final readonly class RetrospectiveFilters
{
    /**
     * @param  list<string>  $repos  full_names; vazio = todos
     * @param  list<ContributionType>  $types
     * @param  'merged'|'open'|'closed'|null  $outcome
     * @param  'total'|'prs'|'lines'  $sort
     */
    public function __construct(
        public CarbonInterface $since,
        public CarbonInterface $until,
        public array $repos = [],
        public array $types = [],
        public ?string $outcome = null,
        public ?string $person = null,
        public bool $hideBots = true,
        public string $sort = 'total',
    ) {}

    public static function period(CarbonInterface $since, CarbonInterface $until): self
    {
        return new self($since, $until, types: ContributionType::cases());
    }

    /**
     * @param  list<string>  $repos
     * @param  list<string>  $types
     */
    public static function make(
        CarbonInterface $since,
        CarbonInterface $until,
        array $repos = [],
        array $types = [],
        ?string $outcome = null,
        ?string $person = null,
        bool $hideBots = true,
        string $sort = 'total',
    ): self {
        $parsedTypes = array_values(array_filter(array_map(
            ContributionType::tryFrom(...),
            $types,
        )));

        return new self(
            since: $since,
            until: $until,
            repos: array_values(array_filter($repos)),
            types: $parsedTypes === [] ? ContributionType::cases() : $parsedTypes,
            outcome: in_array($outcome, ['merged', 'open', 'closed'], true) ? $outcome : null,
            person: ($person === null || $person === '') ? null : $person,
            hideBots: $hideBots,
            sort: in_array($sort, ['total', 'prs', 'lines'], true) ? $sort : 'total',
        );
    }
}
