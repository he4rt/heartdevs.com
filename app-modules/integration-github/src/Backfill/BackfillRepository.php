<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Backfill;

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\GetPullRequest;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListCommits;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssueComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssues;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviewComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviews;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequests;
use Illuminate\Support\Str;
use Saloon\Http\Request;

final readonly class BackfillRepository
{
    private const int PER_PAGE = 100;

    public function __construct(
        private GitHubApiConnector $github,
        private RecordContribution $recorder,
    ) {}

    public function execute(GithubRepository $repository): void
    {
        $tenantId = $repository->tenant_id;
        $repo = $repository->full_name;

        $this->backfillPullRequests($tenantId, $repo);
        $this->backfillIssues($tenantId, $repo);
        $this->backfillIssueComments($tenantId, $repo);
        $this->backfillReviewComments($tenantId, $repo);
        $this->backfillCommits($tenantId, $repo);
    }

    private function backfillPullRequests(string $tenantId, string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequests($repo, $page, self::PER_PAGE),
            function (array $pr) use ($tenantId, $repo): void {
                $number = $this->intFrom($pr, 'number') ?? 0;
                /** @var array<string, mixed> $prSize */
                $prSize = (array) $this->github->send(new GetPullRequest($repo, $number))->throw()->json();
                $login = $this->actorLogin($pr);

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::Pr,
                    externalRef: ContributionType::Pr->ref($number),
                    actorLogin: $login,
                    actorId: $this->actorId($pr),
                    occurredAt: $this->stringFrom($pr, 'created_at'),
                    targetRef: null,
                    metadata: [
                        'title' => data_get($pr, 'title'),
                        'state' => data_get($pr, 'state'),
                        'merged' => data_get($pr, 'merged_at') !== null,
                        'url' => data_get($pr, 'html_url'),
                        'additions' => $this->intFrom($prSize, 'additions') ?? 0,
                        'deletions' => $this->intFrom($prSize, 'deletions') ?? 0,
                        'changed_files' => $this->intFrom($prSize, 'changed_files') ?? 0,
                        'is_bot' => $this->isBot($login),
                    ],
                ));

                $this->backfillReviews($tenantId, $repo, $number);
            },
        );
    }

    private function backfillReviews(string $tenantId, string $repo, int $number): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviews($repo, $number, $page, self::PER_PAGE),
            function (array $review) use ($tenantId, $repo, $number): void {
                $login = $this->actorLogin($review);

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::Review,
                    externalRef: ContributionType::Review->ref($this->stringFrom($review, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($review),
                    occurredAt: $this->stringFrom($review, 'submitted_at'),
                    targetRef: ContributionType::Pr->ref($number),
                    metadata: [
                        'state' => data_get($review, 'state'),
                        'is_bot' => $this->isBot($login),
                    ],
                ));
            },
        );
    }

    private function backfillIssues(string $tenantId, string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssues($repo, $page, self::PER_PAGE),
            function (array $issue) use ($tenantId, $repo): void {
                if (isset($issue['pull_request'])) {
                    return; // o endpoint de issues também devolve PRs; estes já entram via backfillPullRequests
                }

                $login = $this->actorLogin($issue);

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::Issue,
                    externalRef: ContributionType::Issue->ref($this->stringFrom($issue, 'number')),
                    actorLogin: $login,
                    actorId: $this->actorId($issue),
                    occurredAt: $this->stringFrom($issue, 'created_at'),
                    targetRef: null,
                    metadata: [
                        'title' => data_get($issue, 'title'),
                        'state' => data_get($issue, 'state'),
                        'url' => data_get($issue, 'html_url'),
                        'is_bot' => $this->isBot($login),
                    ],
                ));
            },
        );
    }

    private function backfillIssueComments(string $tenantId, string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssueComments($repo, $page, self::PER_PAGE),
            function (array $comment) use ($tenantId, $repo): void {
                $login = $this->actorLogin($comment);

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::Comment,
                    externalRef: ContributionType::Comment->ref($this->stringFrom($comment, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($comment),
                    occurredAt: $this->stringFrom($comment, 'created_at'),
                    targetRef: $this->targetRefFromUrl($this->stringFrom($comment, 'issue_url'), ContributionType::Issue),
                    metadata: [
                        'url' => data_get($comment, 'html_url'),
                        'kind' => 'issue',
                        'is_bot' => $this->isBot($login),
                    ],
                ));
            },
        );
    }

    private function backfillReviewComments(string $tenantId, string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviewComments($repo, $page, self::PER_PAGE),
            function (array $comment) use ($tenantId, $repo): void {
                $login = $this->actorLogin($comment);

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::ReviewComment,
                    externalRef: ContributionType::ReviewComment->ref($this->stringFrom($comment, 'id')),
                    actorLogin: $login,
                    actorId: $this->actorId($comment),
                    occurredAt: $this->stringFrom($comment, 'created_at'),
                    targetRef: $this->targetRefFromUrl($this->stringFrom($comment, 'pull_request_url'), ContributionType::Pr),
                    metadata: [
                        'url' => data_get($comment, 'html_url'),
                        'kind' => 'pr',
                        'is_bot' => $this->isBot($login),
                    ],
                ));
            },
        );
    }

    private function backfillCommits(string $tenantId, string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListCommits($repo, $page, self::PER_PAGE),
            function (array $commit) use ($tenantId, $repo): void {
                $login = $this->stringFrom($commit, 'author.login')
                    ?: $this->stringFrom($commit, 'commit.author.name', 'ghost');

                $this->recorder->execute(new NewContributionDTO(
                    tenantId: $tenantId,
                    repo: $repo,
                    type: ContributionType::Commit,
                    externalRef: ContributionType::Commit->ref($this->stringFrom($commit, 'sha')),
                    actorLogin: $login,
                    actorId: $this->intFrom($commit, 'author.id'),
                    occurredAt: $this->stringFrom($commit, 'commit.author.date'),
                    targetRef: null,
                    metadata: [
                        'url' => data_get($commit, 'html_url'),
                        'is_bot' => $this->isBot($login),
                    ],
                ));
            },
        );
    }

    /**
     * @param  callable(int): Request  $requestFor
     * @param  callable(array<string, mixed>): void  $onEach
     */
    private function paginate(callable $requestFor, callable $onEach): void
    {
        $page = 1;

        do {
            /** @var list<array<string, mixed>> $items */
            $items = (array) $this->github->send($requestFor($page))->throw()->json();

            foreach ($items as $item) {
                $onEach($item);
            }

            $page++;
        } while (count($items) === self::PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stringFrom(array $data, string $key, string $default = ''): string
    {
        $value = data_get($data, $key);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function intFrom(array $data, string $key): ?int
    {
        $value = data_get($data, $key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function actorLogin(array $payload): string
    {
        return $this->stringFrom($payload, 'user.login', 'ghost');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function actorId(array $payload): ?int
    {
        return $this->intFrom($payload, 'user.id');
    }

    private function targetRefFromUrl(string $url, ContributionType $kind): ?string
    {
        $number = Str::match('~/(\d+)$~', $url);

        return $number === '' ? null : $kind->ref($number);
    }

    private function isBot(string $login): bool
    {
        return Str::endsWith($login, '[bot]');
    }
}
