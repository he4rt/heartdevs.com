<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Retrospective\CommunityRetrospective;

beforeEach(function (): void {
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
});

it('agrega contribuições por pessoa com contagem por tipo e total, ordenado desc', function (): void {
    GithubContribution::factory()->create(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    GithubContribution::factory()->create(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-06-03']);
    GithubContribution::factory()->create(['actor_login' => 'joao', 'actor_id' => 7, 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-03']);

    $data = new CommunityRetrospective($this->since, $this->until)->build();

    expect($data['meta']['people'])->toBe(2)
        ->and($data['meta']['total'])->toBe(3)
        ->and($data['people'][0]['login'])->toBe('maria')
        ->and($data['people'][0]['total'])->toBe(2)
        ->and($data['people'][0]['prs'])->toBe(1)
        ->and($data['people'][0]['issues'])->toBe(1)
        ->and($data['people'][0]['avatar'])->toContain('42');
});

it('exclui bots do ranking', function (): void {
    GithubContribution::factory()->create(['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    GithubContribution::factory()->create(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $data = new CommunityRetrospective($this->since, $this->until)->build();

    expect($data['meta']['people'])->toBe(1)
        ->and($data['people'][0]['login'])->toBe('maria');
});

it('inclui PRs fechados sem merge no total, distinguindo por desfecho', function (): void {
    // a mesma pessoa com os três desfechos: fechado-sem-merge, merged e aberto
    GithubContribution::factory()->create(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false]]);
    GithubContribution::factory()->create(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true]]);
    GithubContribution::factory()->create(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $data = new CommunityRetrospective($this->since, $this->until)->build();

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
    GithubContribution::factory()->create(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-05-30']);
    GithubContribution::factory()->create(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:2', 'occurred_at' => '2026-06-03']);

    $data = new CommunityRetrospective($this->since, $this->until)->build();

    expect($data['meta']['total'])->toBe(1)
        ->and($data['meta']['issues'])->toBe(1);
});
