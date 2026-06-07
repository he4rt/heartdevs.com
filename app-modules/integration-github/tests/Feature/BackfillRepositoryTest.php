<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Backfill\BackfillRepository;
use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\GetPullRequest;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListCommits;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssueComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssues;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviewComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviews;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequests;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    $this->repo = GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);
});

/**
 * Mocks every GitHub endpoint the backfill touches with an empty page by default,
 * so each test only declares the endpoint it cares about.
 *
 * @param  array<class-string, MockResponse>  $overrides
 */
function mockGithub(array $overrides = []): void
{
    $defaults = [
        ListPullRequests::class => MockResponse::make([]),
        GetPullRequest::class => MockResponse::make([]),
        ListPullRequestReviews::class => MockResponse::make([]),
        ListIssues::class => MockResponse::make([]),
        ListIssueComments::class => MockResponse::make([]),
        ListPullRequestReviewComments::class => MockResponse::make([]),
        ListCommits::class => MockResponse::make([]),
    ];

    app()->instance(GitHubApiConnector::class, tap(
        new GitHubApiConnector(),
        fn (GitHubApiConnector $connector) => $connector->withMockClient(new MockClient([...$defaults, ...$overrides])),
    ));
}

/**
 * @return array<string, mixed>
 */
function prPayload(int $number, string $login, int $id, ?string $merged = null): array
{
    return [
        'number' => $number,
        'title' => 'feat: pr '.$number,
        'state' => 'open',
        'created_at' => '2026-06-01T12:00:00Z',
        'merged_at' => $merged,
        'html_url' => 'https://github.com/he4rt/heartdevs.com/pull/'.$number,
        'user' => ['login' => $login, 'id' => $id],
    ];
}

function backfill(GithubRepository $repo): void
{
    resolve(BackfillRepository::class)->execute($repo);
}

it('faz backfill de PRs upsertando contributions com tamanho, autor e tenant', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(1, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 10, 'deletions' => 2, 'changed_files' => 3]),
    ]);

    backfill($this->repo);

    $contribution = GithubContribution::query()->where('external_ref', 'pr:1')->sole();

    expect($contribution->type)->toBe(ContributionType::Pr)
        ->and($contribution->tenant_id)->toBe($this->repo->tenant_id)
        ->and($contribution->actor_login)->toBe('maria')
        ->and($contribution->actor_id)->toBe(42)
        ->and($contribution->repo)->toBe('he4rt/heartdevs.com')
        ->and($contribution->occurred_at->toIso8601String())->toBe('2026-06-01T12:00:00+00:00')
        ->and($contribution->metadata['additions'])->toBe(10)
        ->and($contribution->metadata['deletions'])->toBe(2)
        ->and($contribution->metadata['is_bot'])->toBeFalse();
});

it('ignora reviews PENDING (sem submitted_at) sem quebrar o backfill', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(61, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
        ListPullRequestReviews::class => MockResponse::make([
            ['id' => 1597160462, 'state' => 'PENDING', 'submitted_at' => null, 'user' => ['login' => 'danielhe4rt', 'id' => 6912596]],
            ['id' => 1597160999, 'state' => 'APPROVED', 'submitted_at' => '2026-06-02T10:00:00Z', 'user' => ['login' => 'maria', 'id' => 42]],
        ]),
    ]);

    backfill($this->repo);

    expect(GithubContribution::query()->where('type', ContributionType::Review)->pluck('external_ref')->all())
        ->toBe(['review:1597160999']);
});

it('reporta o progresso por contribuição via callback (tipo + isNew, na ordem de ingestão)', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(1, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
        ListPullRequestReviews::class => MockResponse::make([
            ['id' => 10, 'state' => 'APPROVED', 'submitted_at' => '2026-06-02T10:00:00Z', 'user' => ['login' => 'joao', 'id' => 7]],
        ]),
        ListIssues::class => MockResponse::make([
            ['number' => 5, 'created_at' => '2026-06-01T00:00:00Z', 'user' => ['login' => 'ana', 'id' => 3]],
        ]),
    ]);

    $reported = [];
    resolve(BackfillRepository::class)->execute(
        $this->repo,
        function (NewContributionDTO $contribution, bool $isNew) use (&$reported): void {
            $reported[] = [$contribution->type->value, $isNew];
        },
    );

    expect($reported)->toBe([['pr', true], ['review', true], ['issue', true]]);
});

it('sinaliza isNew=false ao reprocessar contribuições que já existem', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(1, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
    ]);

    backfill($this->repo); // 1ª passada: cria pr:1

    $reported = [];
    resolve(BackfillRepository::class)->execute(
        $this->repo,
        function (NewContributionDTO $contribution, bool $isNew) use (&$reported): void {
            $reported[] = $isNew;
        },
    );

    // pr:1 já existia: updateOrCreate reatualiza a linha, não cria — logo isNew=false.
    expect($reported)->toBe([false]);
});

it('incremental: para nos PRs anteriores ao corte (updated < since)', function (): void {
    $repo = GithubRepository::factory()->create([
        'full_name' => 'he4rt/inc',
        'last_backfilled_at' => '2026-06-05 00:00:00',
    ]);

    // PRs vêm por updated desc; since = last_backfilled_at - 1d = 2026-06-04.
    mockGithub([
        ListPullRequests::class => MockResponse::make([
            ['number' => 10, 'updated_at' => '2026-06-06T10:00:00Z', 'created_at' => '2026-06-06T09:00:00Z', 'state' => 'open', 'merged_at' => null, 'user' => ['login' => 'recente', 'id' => 1]],
            ['number' => 2, 'updated_at' => '2026-06-01T10:00:00Z', 'created_at' => '2026-06-01T09:00:00Z', 'state' => 'open', 'merged_at' => null, 'user' => ['login' => 'antigo', 'id' => 2]],
        ]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
    ]);

    backfill($repo);

    expect(GithubContribution::query()->where('repo', 'he4rt/inc')->where('type', ContributionType::Pr)->pluck('external_ref')->all())
        ->toBe(['pr:10']);
});

it('varredura completa (sem last_backfilled_at) pega todos os PRs, ignorando updated_at', function (): void {
    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/full']);

    mockGithub([
        ListPullRequests::class => MockResponse::make([
            ['number' => 10, 'updated_at' => '2026-06-06T10:00:00Z', 'created_at' => '2026-06-06T09:00:00Z', 'state' => 'open', 'merged_at' => null, 'user' => ['login' => 'a', 'id' => 1]],
            ['number' => 2, 'updated_at' => '2018-01-01T10:00:00Z', 'created_at' => '2018-01-01T09:00:00Z', 'state' => 'open', 'merged_at' => null, 'user' => ['login' => 'b', 'id' => 2]],
        ]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
    ]);

    backfill($repo);

    expect(GithubContribution::query()->where('repo', 'he4rt/full')->where('type', ContributionType::Pr)->pluck('external_ref')->sort()->values()->all())
        ->toBe(['pr:10', 'pr:2']);
});

it('full=true ignora o last_backfilled_at e varre tudo', function (): void {
    $repo = GithubRepository::factory()->create([
        'full_name' => 'he4rt/forced',
        'last_backfilled_at' => '2026-06-05 00:00:00',
    ]);

    mockGithub([
        ListPullRequests::class => MockResponse::make([
            ['number' => 2, 'updated_at' => '2018-01-01T10:00:00Z', 'created_at' => '2018-01-01T09:00:00Z', 'state' => 'open', 'merged_at' => null, 'user' => ['login' => 'antigo', 'id' => 2]],
        ]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
    ]);

    resolve(BackfillRepository::class)->execute($repo, full: true);

    expect(GithubContribution::query()->where('repo', 'he4rt/forced')->where('type', ContributionType::Pr)->pluck('external_ref')->all())
        ->toBe(['pr:2']);
});

it('é idempotente: re-rodar o backfill não duplica', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(1, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 10, 'deletions' => 2, 'changed_files' => 3]),
    ]);

    backfill($this->repo);
    backfill($this->repo);

    expect(GithubContribution::query()->where('external_ref', 'pr:1')->count())->toBe(1);
});

it('marca is_bot para autores [bot]', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(7, 'dependabot[bot]', 99)]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
    ]);

    backfill($this->repo);

    expect(GithubContribution::query()->where('external_ref', 'pr:7')->sole()->metadata['is_bot'])->toBeTrue();
});

it('faz backfill das reviews de um PR com target_ref para o PR', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make([prPayload(1, 'maria', 42)]),
        GetPullRequest::class => MockResponse::make(['additions' => 1, 'deletions' => 0, 'changed_files' => 1]),
        ListPullRequestReviews::class => MockResponse::make([
            ['id' => 555, 'state' => 'APPROVED', 'submitted_at' => '2026-06-02T09:00:00Z', 'user' => ['login' => 'joao', 'id' => 7]],
        ]),
    ]);

    backfill($this->repo);

    $review = GithubContribution::query()->where('type', ContributionType::Review)->sole();

    expect($review->external_ref)->toBe('review:555')
        ->and($review->target_ref)->toBe('pr:1')
        ->and($review->actor_login)->toBe('joao')
        ->and($review->occurred_at->toIso8601String())->toBe('2026-06-02T09:00:00+00:00');
});

it('faz backfill de issues ignorando os PRs retornados pelo endpoint de issues', function (): void {
    mockGithub([
        ListIssues::class => MockResponse::make([
            ['number' => 10, 'title' => 'bug: x', 'state' => 'open', 'created_at' => '2026-06-01T00:00:00Z', 'html_url' => 'u', 'user' => ['login' => 'ana', 'id' => 3]],
            ['number' => 11, 'title' => 'na verdade um PR', 'pull_request' => ['url' => 'x'], 'created_at' => '2026-06-01T00:00:00Z', 'html_url' => 'u', 'user' => ['login' => 'x', 'id' => 1]],
        ]),
    ]);

    backfill($this->repo);

    expect(GithubContribution::query()->where('type', ContributionType::Issue)->pluck('external_ref')->all())
        ->toBe(['issue:10']);
});

it('faz backfill de comentários de issue com target_ref derivado da issue_url', function (): void {
    mockGithub([
        ListIssueComments::class => MockResponse::make([
            ['id' => 900, 'created_at' => '2026-06-03T10:00:00Z', 'html_url' => 'u', 'issue_url' => 'https://api.github.com/repos/he4rt/heartdevs.com/issues/10', 'user' => ['login' => 'ana', 'id' => 3]],
        ]),
    ]);

    backfill($this->repo);

    $comment = GithubContribution::query()->where('type', ContributionType::Comment)->sole();

    expect($comment->external_ref)->toBe('comment:900')
        ->and($comment->target_ref)->toBe('issue:10')
        ->and($comment->actor_login)->toBe('ana');
});

it('faz backfill de comentários de review de PR com target_ref para o PR', function (): void {
    mockGithub([
        ListPullRequestReviewComments::class => MockResponse::make([
            ['id' => 1200, 'created_at' => '2026-06-03T11:00:00Z', 'html_url' => 'u', 'pull_request_url' => 'https://api.github.com/repos/he4rt/heartdevs.com/pulls/12', 'user' => ['login' => 'joao', 'id' => 7]],
        ]),
    ]);

    backfill($this->repo);

    $comment = GithubContribution::query()->where('external_ref', 'review_comment:1200')->sole();

    expect($comment->type)->toBe(ContributionType::ReviewComment)
        ->and($comment->target_ref)->toBe('pr:12');
});

it('propaga o erro do GitHub em vez de processar a resposta de falha como dados', function (): void {
    mockGithub([
        ListPullRequests::class => MockResponse::make(['message' => 'Internal Server Error'], 500),
    ]);

    expect(fn (): mixed => resolve(BackfillRepository::class)->execute($this->repo))
        ->toThrow(RequestException::class);
});

it('faz backfill de commits dedup por sha, com fallback de autor', function (): void {
    mockGithub([
        ListCommits::class => MockResponse::make([
            ['sha' => 'abc123', 'html_url' => 'u', 'commit' => ['author' => ['name' => 'Maria', 'date' => '2026-06-04T08:00:00Z']], 'author' => ['login' => 'maria', 'id' => 42]],
            ['sha' => 'def456', 'html_url' => 'u', 'commit' => ['author' => ['name' => 'Sem Conta', 'date' => '2026-06-04T09:00:00Z']], 'author' => null],
        ]),
    ]);

    backfill($this->repo);

    $linked = GithubContribution::query()->where('external_ref', 'commit:abc123')->sole();
    $unlinked = GithubContribution::query()->where('external_ref', 'commit:def456')->sole();

    expect($linked->type)->toBe(ContributionType::Commit)
        ->and($linked->actor_login)->toBe('maria')
        ->and($linked->actor_id)->toBe(42)
        ->and($linked->occurred_at->toIso8601String())->toBe('2026-06-04T08:00:00+00:00')
        ->and($unlinked->actor_login)->toBe('Sem Conta')
        ->and($unlinked->actor_id)->toBeNull();
});
