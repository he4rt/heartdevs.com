<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Retrospective\GithubSource;

/**
 * Golden test do cálculo do GitHub. Herdado do antigo read model do portal
 * (CommunityRetrospective), agora validando o GithubSource: mesma agregação,
 * mesmos números. Os filtros ricos do visitante (tipo/repo/desfecho/pessoa/sort)
 * foram aposentados na Fase 1, então seus casos saíram.
 */
beforeEach(function (): void {
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
    $this->collect = fn (bool $hideBots = true): SourceResult => new GithubSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(hideBots: $hideBots),
    );
});

/**
 * @param  array<string, mixed>  $attributes
 */
function ghContribution(array $attributes): GithubContribution
{
    return GithubContribution::factory()->create($attributes);
}

/**
 * @return array<string, mixed>
 */
function ghSlide(SourceResult $result, string $kind): array
{
    foreach ($result->slides as $slide) {
        if ($slide->kind() === $kind) {
            return $slide->toArray();
        }
    }

    return [];
}

/**
 * @return array<string, int>
 */
function ghMeta(SourceResult $result): array
{
    return ghSlide($result, 'github.panorama')['meta'] ?? [];
}

/**
 * @return list<array<string, mixed>>
 */
function ghPeople(SourceResult $result): array
{
    return ghSlide($result, 'github.core')['people'] ?? [];
}

/**
 * @return list<array<string, mixed>>
 */
function ghRepos(SourceResult $result): array
{
    return collect($result->slides)
        ->filter(fn ($slide): bool => $slide->kind() === 'github.repos')
        ->map(fn ($slide): array => $slide->toArray()['repo'])
        ->values()
        ->all();
}

/**
 * @return list<array<string, mixed>>
 */
function ghHighlights(SourceResult $result): array
{
    return ghSlide($result, 'github.highlights')['highlights'] ?? [];
}

it('identifica-se como a fonte github', function (): void {
    expect(new GithubSource()->key())->toBe('github');
});

it('agrega contribuições por pessoa com contagem por tipo e total, ordenado desc', function (): void {
    ghContribution(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'actor_id' => 42, 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-06-03']);
    ghContribution(['actor_login' => 'joao', 'actor_id' => 7, 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-03']);

    $result = ($this->collect)();
    $meta = ghMeta($result);
    $people = ghPeople($result);

    expect($meta['people'])->toBe(2)
        ->and($meta['total'])->toBe(3)
        ->and($people[0]['login'])->toBe('maria')
        ->and($people[0]['total'])->toBe(2)
        ->and($people[0]['prs'])->toBe(1)
        ->and($people[0]['issues'])->toBe(1)
        ->and($people[0]['avatar'])->toContain('42');
});

it('exclui bots do ranking', function (): void {
    ghContribution(['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $result = ($this->collect)();

    expect(ghMeta($result)['people'])->toBe(1)
        ->and(ghPeople($result)[0]['login'])->toBe('maria');
});

it('mantém bots quando hideBots é falso', function (): void {
    ghContribution(['actor_login' => 'dependabot[bot]', 'type' => ContributionType::Pr, 'external_ref' => 'pr:9', 'occurred_at' => '2026-06-02', 'metadata' => ['is_bot' => true, 'state' => 'open', 'merged' => false]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    expect(ghMeta(($this->collect)(false))['people'])->toBe(2);
});

it('inclui PRs fechados sem merge no total, distinguindo por desfecho', function (): void {
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => false]]);
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'closed', 'merged' => true]]);
    ghContribution(['actor_login' => 'a', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false]]);

    $result = ($this->collect)();
    $meta = ghMeta($result);
    $person = collect(ghPeople($result))->firstWhere('login', 'a');

    expect($meta['prs'])->toBe(3)
        ->and($meta['prs_merged'])->toBe(1)
        ->and($meta['prs_unmerged'])->toBe(1)
        ->and($person['prs'])->toBe(3)
        ->and($person['prs_merged'])->toBe(1)
        ->and($person['prs_unmerged'])->toBe(1)
        ->and($person['total'])->toBe(3);
});

it('conta comentários de issue e de review separadamente', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Comment, 'external_ref' => 'comment:1', 'occurred_at' => '2026-06-02']);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::ReviewComment, 'external_ref' => 'review_comment:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();
    $maria = collect(ghPeople($result))->firstWhere('login', 'maria');

    expect(ghMeta($result)['comments'])->toBe(1)
        ->and(ghMeta($result)['review_comments'])->toBe(1)
        ->and($maria['comments'])->toBe(1)
        ->and($maria['review_comments'])->toBe(1)
        ->and($maria['total'])->toBe(2);
});

it('respeita a janela de período pelo occurred_at', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:1', 'occurred_at' => '2026-05-30']);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:2', 'occurred_at' => '2026-06-03']);

    $meta = ghMeta(($this->collect)());

    expect($meta['total'])->toBe(1)
        ->and($meta['issues'])->toBe(1);
});

it('soma additions/deletions/changed_files de PRs em meta e por pessoa', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'additions' => 100, 'deletions' => 20, 'changed_files' => 5]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'additions' => 30, 'deletions' => 4, 'changed_files' => 2]]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();
    $maria = collect(ghPeople($result))->firstWhere('login', 'maria');

    expect(ghMeta($result)['additions'])->toBe(130)
        ->and(ghMeta($result)['deletions'])->toBe(24)
        ->and(ghMeta($result)['changed_files'])->toBe(7)
        ->and($maria['additions'])->toBe(130)
        ->and($maria['deletions'])->toBe(24);
});

it('expõe refs de PR e issue por pessoa para os chips de atividade', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:290', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'feat: x', 'url' => 'https://github.com/he4rt/heartdevs.com/pull/290']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Issue, 'external_ref' => 'issue:12', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'bug: y', 'url' => 'https://github.com/he4rt/heartdevs.com/issues/12']]);

    $maria = collect(ghPeople(($this->collect)()))->firstWhere('login', 'maria');

    expect($maria['pr_refs'])->toHaveCount(1)
        ->and($maria['pr_refs'][0])->toMatchArray(['num' => 290, 'title' => 'feat: x', 'state' => 'merged'])
        ->and($maria['pr_refs'][0]['url'])->toContain('/pull/290')
        ->and($maria['issue_refs'])->toHaveCount(1)
        ->and($maria['issue_refs'][0])->toMatchArray(['num' => 12, 'title' => 'bug: y']);
});

it('ordena os refs de PR do mais recente para o mais antigo', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-01', 'metadata' => ['title' => 'antigo', 'url' => 'u1', 'state' => 'merged']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-05', 'metadata' => ['title' => 'recente', 'url' => 'u2', 'state' => 'open']]);
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:3', 'occurred_at' => '2026-06-03', 'metadata' => ['title' => 'meio', 'url' => 'u3', 'state' => 'open']]);

    $maria = collect(ghPeople(($this->collect)()))->firstWhere('login', 'maria');

    expect(array_column($maria['pr_refs'], 'num'))->toBe([2, 3, 1]);
});

it('agrupa PRs por repositório e lista destaques por linhas mudadas', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/heartdevs.com', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 'grande', 'url' => 'u1', 'additions' => 500, 'deletions' => 100, 'changed_files' => 10]]);
    ghContribution(['actor_login' => 'joao', 'repo' => 'he4rt/bot', 'type' => ContributionType::Pr, 'external_ref' => 'pr:2', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'open', 'merged' => false, 'title' => 'pequeno', 'url' => 'u2', 'additions' => 10, 'deletions' => 2, 'changed_files' => 1]]);

    $result = ($this->collect)();
    $repos = ghRepos($result);
    $highlights = ghHighlights($result);

    expect($repos)->toHaveCount(2)
        ->and(collect($repos)->firstWhere('full_name', 'he4rt/heartdevs.com')['name'])->toBe('heartdevs.com')
        ->and(collect($repos)->firstWhere('full_name', 'he4rt/heartdevs.com')['prs'])->toHaveCount(1)
        ->and($highlights[0]['num'])->toBe(1)
        ->and($highlights[0]['additions'])->toBe(500)
        ->and($highlights[0]['repo'])->toBe('he4rt/heartdevs.com');
});

it('esconde da lista repos sem PR no recorte mas mantém suas contribuições nas stats', function (): void {
    ghContribution(['actor_login' => 'maria', 'repo' => 'he4rt/com-pr', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'title' => 't', 'url' => 'u', 'additions' => 5, 'deletions' => 1, 'changed_files' => 1]]);
    ghContribution(['actor_login' => 'joao', 'repo' => 'he4rt/so-review', 'type' => ContributionType::Review, 'external_ref' => 'review:1', 'occurred_at' => '2026-06-02']);

    $result = ($this->collect)();

    expect(collect(ghRepos($result))->pluck('full_name')->all())->toBe(['he4rt/com-pr'])
        ->and(ghMeta($result)['reviews'])->toBe(1)
        ->and(ghMeta($result)['repos'])->toBe(1);
});

it('devolve resultado vazio quando não há contribuições no recorte', function (): void {
    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('monta os chips do cover a partir do meta', function (): void {
    ghContribution(['actor_login' => 'maria', 'type' => ContributionType::Pr, 'external_ref' => 'pr:1', 'occurred_at' => '2026-06-02', 'metadata' => ['state' => 'merged', 'merged' => true, 'additions' => 10, 'deletions' => 5]]);

    $result = ($this->collect)();
    $labels = collect($result->headline->metrics)->map(fn ($metric): string => $metric->label)->all();

    expect($result->label)->toBe('GitHub')
        ->and($labels)->toContain('Pessoas')
        ->and($labels)->toContain('PRs')
        ->and($labels)->toContain('Linhas');
});
