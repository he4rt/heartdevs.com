<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Retrospective\CommunityRetrospective;
use He4rt\Portal\Retrospective\RetrospectiveFilters;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
    $this->build = fn (?RetrospectiveFilters $filters = null): array => new CommunityRetrospective(
        $this->tenant->id,
        $filters ?? RetrospectiveFilters::period($this->since, $this->until),
    )->build();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function contribution(Tenant $tenant, array $attributes): GithubContribution
{
    return GithubContribution::factory()->for($tenant)->create($attributes);
}

it('agrega contribuições por pessoa com contagem por tipo e total, ordenado desc', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    contribution($this->tenant, ['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-06-03']);
    contribution($this->tenant, ['actor_login' => 'joao', 'actor_id' => 7, 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-03']);

    $data = ($this->build)();

    expect($data['meta']['people'])->toBe(2)
        ->and($data['meta']['total'])->toBe(3)
        ->and($data['people'][0]['login'])->toBe('maria')
        ->and($data['people'][0]['total'])->toBe(2)
        ->and($data['people'][0]['prs'])->toBe(1)
        ->and($data['people'][0]['issues'])->toBe(1)
        ->and($data['people'][0]['avatar'])->toContain('42');
});

it('exclui bots do ranking', function (): void {
    contribution($this->tenant, ['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $data = ($this->build)();

    expect($data['meta']['people'])->toBe(1)
        ->and($data['people'][0]['login'])->toBe('maria');
});

it('isola por tenant: ignora contribuições de outra comunidade', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    contribution(Tenant::factory()->create(), ['actor_login' => 'estranho', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $data = ($this->build)();

    expect($data['meta']['people'])->toBe(1)
        ->and($data['people'][0]['login'])->toBe('maria');
});

it('inclui PRs fechados sem merge no total, distinguindo por desfecho', function (): void {
    contribution($this->tenant, ['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false]]);
    contribution($this->tenant, ['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true]]);
    contribution($this->tenant, ['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $data = ($this->build)();

    $person = collect($data['people'])->firstWhere('login', 'a');

    expect($data['meta']['prs'])->toBe(3)
        ->and($data['meta']['prs_merged'])->toBe(1)
        ->and($data['meta']['prs_unmerged'])->toBe(1)
        ->and($person['prs'])->toBe(3)
        ->and($person['prs_merged'])->toBe(1)
        ->and($person['prs_unmerged'])->toBe(1)
        ->and($person['total'])->toBe(3);
});

it('respeita a janela de período pelo occurred_at', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-05-30']);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:2', 'occurred_at' => '2026-06-03']);

    $data = ($this->build)();

    expect($data['meta']['total'])->toBe(1)
        ->and($data['meta']['issues'])->toBe(1);
});

it('soma additions/deletions/changed_files de PRs em meta e por pessoa', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'additions' => 100, 'deletions' => 20, 'changed_files' => 5]]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'additions' => 30, 'deletions' => 4, 'changed_files' => 2]]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $data = ($this->build)();
    $maria = collect($data['people'])->firstWhere('login', 'maria');

    expect($data['meta']['additions'])->toBe(130)
        ->and($data['meta']['deletions'])->toBe(24)
        ->and($data['meta']['changed_files'])->toBe(7)
        ->and($maria['additions'])->toBe(130)
        ->and($maria['deletions'])->toBe(24);
});
