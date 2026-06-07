<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\Portal\Retrospective\CommunityRetrospective;
use He4rt\Portal\Retrospective\RetrospectiveFilters;
use Illuminate\Support\Facades\Blade;

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

it('conta comentários de issue e de review separadamente', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Comment, 'external_ref' => 'comment:1', 'occurred_at' => '2026-06-02']);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::ReviewComment, 'external_ref' => 'review_comment:1', 'occurred_at' => '2026-06-02']);

    $data = ($this->build)();
    $maria = collect($data['people'])->firstWhere('login', 'maria');

    expect($data['meta']['comments'])->toBe(1)
        ->and($data['meta']['review_comments'])->toBe(1)
        ->and($maria['comments'])->toBe(1)
        ->and($maria['review_comments'])->toBe(1)
        ->and($maria['total'])->toBe(2);
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

it('expõe refs de PR e issue por pessoa para os chips de atividade', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:290', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'feat: x', 'url' => 'https://github.com/he4rt/heartdevs.com/pull/290']]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:12', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'bug: y', 'url' => 'https://github.com/he4rt/heartdevs.com/issues/12']]);

    $data = ($this->build)();
    $maria = collect($data['people'])->firstWhere('login', 'maria');

    expect($maria['pr_refs'])->toHaveCount(1)
        ->and($maria['pr_refs'][0])->toMatchArray(['num' => 290, 'title' => 'feat: x', 'state' => 'merged'])
        ->and($maria['pr_refs'][0]['url'])->toContain('/pull/290')
        ->and($maria['issue_refs'])->toHaveCount(1)
        ->and($maria['issue_refs'][0])->toMatchArray(['num' => 12, 'title' => 'bug: y']);
});

it('ordena os refs de PR do mais recente para o mais antigo (para o "últimos N")', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-01', 'metadata' => ['title' => 'antigo', 'url' => 'u1', 'state' => 'merged']]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-05', 'metadata' => ['title' => 'recente', 'url' => 'u2', 'state' => 'open']]);
    contribution($this->tenant, ['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'meio', 'url' => 'u3', 'state' => 'open']]);

    $maria = collect(($this->build)()['people'])->firstWhere('login', 'maria');

    expect(array_column($maria['pr_refs'], 'num'))->toBe([2, 3, 1]);
});

it('na view, mostra só os 3 primeiros refs e um chip "mais X…"', function (): void {
    $person = [
        'pr_refs' => array_map(
            fn (int $n): array => ['num' => $n, 'title' => 'pr '.$n, 'url' => null, 'state' => 'open'],
            [10, 9, 8, 7, 6],
        ),
        'issue_refs' => [],
        'reviews' => 0,
        'comments' => 0,
        'review_comments' => 0,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-chips :person="$person" />', ['person' => $person]);

    expect($html)->toContain('#10')
        ->and($html)->toContain('#9')
        ->and($html)->toContain('#8')
        ->and($html)->not->toContain('#7')
        ->and($html)->not->toContain('#6')
        ->and($html)->toContain('mais 2…');
});

it('o card compacto da comunidade mostra só ícone + somatória dos tipos com contribuição', function (): void {
    $person = [
        'pr_refs' => [
            ['num' => 1, 'title' => 'feat: a', 'url' => null, 'state' => 'open'],
            ['num' => 2, 'title' => 'feat: b', 'url' => null, 'state' => 'merged'],
        ],
        'issue_refs' => [],
        'reviews' => 5,
        'comments' => 0,
        'review_comments' => 3,
        'commits' => 0,
    ];

    $html = Blade::render('<x-portal::retro.activity-icons :person="$person" />', ['person' => $person]);

    // 3 tipos com n>0: PR(2), review(5), review_comment(3) → 3 pills, sem zeros
    expect(mb_substr_count($html, 'class="cstat '))->toBe(3)
        ->and($html)->toContain('<span class="cstat-n">2</span>')
        ->and($html)->toContain('<span class="cstat-n">5</span>')
        ->and($html)->toContain('<span class="cstat-n">3</span>')
        ->and($html)->not->toContain('feat:'); // sem títulos de PR
});

it('agrupa PRs por repositório e lista destaques por linhas mudadas', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'grande', 'url' => 'u1', 'additions' => 500, 'deletions' => 100, 'changed_files' => 10]]);
    contribution($this->tenant, ['actor_login' => 'joao', 'repo' => 'he4rt/bot', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'title' => 'pequeno', 'url' => 'u2', 'additions' => 10, 'deletions' => 2, 'changed_files' => 1]]);

    $data = ($this->build)();

    expect($data['repos'])->toHaveCount(2)
        ->and(collect($data['repos'])->firstWhere('full_name', 'he4rt/heartdevs.com')['name'])->toBe('heartdevs.com')
        ->and(collect($data['repos'])->firstWhere('full_name', 'he4rt/heartdevs.com')['prs'])->toHaveCount(1)
        ->and($data['highlights'][0]['num'])->toBe(1)
        ->and($data['highlights'][0]['additions'])->toBe(500)
        ->and($data['highlights'][0]['repo'])->toBe('he4rt/heartdevs.com');
});

it('aplica filtros de tipo, repo, desfecho e pessoa', function (): void {
    contribution($this->tenant, ['actor_login' => 'maria', 'repo' => 'he4rt/a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true]]);
    contribution($this->tenant, ['actor_login' => 'maria', 'repo' => 'he4rt/a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    contribution($this->tenant, ['actor_login' => 'joao', 'repo' => 'he4rt/b', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $filters = RetrospectiveFilters::make($this->since, $this->until, repos: ['he4rt/a'], types: ['pr'], outcome: 'merged', person: 'maria');
    $data = ($this->build)($filters);

    expect($data['meta']['total'])->toBe(1)
        ->and($data['meta']['people'])->toBe(1)
        ->and($data['people'][0]['login'])->toBe('maria')
        ->and($data['people'][0]['prs'])->toBe(1);
});

it('ordena o ranking por linhas quando sort=lines', function (): void {
    contribution($this->tenant, ['actor_login' => 'poucas', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'additions' => 10, 'deletions' => 0]]);
    contribution($this->tenant, ['actor_login' => 'muitas', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'additions' => 900, 'deletions' => 50]]);

    $filters = RetrospectiveFilters::make($this->since, $this->until, sort: 'lines');
    $data = ($this->build)($filters);

    expect($data['people'][0]['login'])->toBe('muitas');
});
