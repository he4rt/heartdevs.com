<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Backfill;

use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\GetPullRequest;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListCommits;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssueComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListIssues;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviewComments;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequestReviews;
use He4rt\IntegrationGithub\Transport\Requests\Contributions\ListPullRequests;
use Saloon\Http\Request;

final readonly class BackfillRepository
{
    private const int PER_PAGE = 100;

    public function __construct(
        private GitHubApiConnector $github,
        private RecordContribution $recorder,
    ) {}

    public function execute(string $repo): void
    {
        $this->backfillPullRequests($repo);
        $this->backfillIssues($repo);
        $this->backfillIssueComments($repo);
        $this->backfillReviewComments($repo);
        $this->backfillCommits($repo);
    }

    private function backfillPullRequests(string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequests($repo, $page, self::PER_PAGE),
            function (array $pr) use ($repo): void {
                $number = (int) ($pr['number'] ?? 0);
                /** @var array<string, mixed> $detail */
                $detail = (array) $this->github->send(new GetPullRequest($repo, $number))->throw()->json();
                $login = $this->login($pr);

                $this->upsert($repo, ContributionType::Pr, 'pr:'.$number, $login, $this->userId($pr), $this->strv($pr, 'created_at'), null, [
                    'title' => $pr['title'] ?? null,
                    'state' => $pr['state'] ?? null,
                    'merged' => ($pr['merged_at'] ?? null) !== null,
                    'url' => $pr['html_url'] ?? null,
                    'additions' => (int) ($detail['additions'] ?? 0),
                    'deletions' => (int) ($detail['deletions'] ?? 0),
                    'changed_files' => (int) ($detail['changed_files'] ?? 0),
                    'is_bot' => $this->isBot($login),
                ]);

                $this->backfillReviews($repo, $number);
            },
        );
    }

    private function backfillReviews(string $repo, int $number): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviews($repo, $number, $page, self::PER_PAGE),
            function (array $review) use ($repo, $number): void {
                $login = $this->login($review);

                $this->upsert($repo, ContributionType::Review, 'review:'.($review['id'] ?? ''), $login, $this->userId($review), $this->strv($review, 'submitted_at'), 'pr:'.$number, [
                    'state' => $review['state'] ?? null,
                    'is_bot' => $this->isBot($login),
                ]);
            },
        );
    }

    private function backfillIssues(string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssues($repo, $page, self::PER_PAGE),
            function (array $issue) use ($repo): void {
                if (isset($issue['pull_request'])) {
                    return; // o endpoint de issues também devolve PRs; estes já entram via backfillPullRequests
                }

                $login = $this->login($issue);

                $this->upsert($repo, ContributionType::Issue, 'issue:'.($issue['number'] ?? ''), $login, $this->userId($issue), $this->strv($issue, 'created_at'), null, [
                    'title' => $issue['title'] ?? null,
                    'state' => $issue['state'] ?? null,
                    'url' => $issue['html_url'] ?? null,
                    'is_bot' => $this->isBot($login),
                ]);
            },
        );
    }

    private function backfillIssueComments(string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListIssueComments($repo, $page, self::PER_PAGE),
            function (array $comment) use ($repo): void {
                $login = $this->login($comment);

                $this->upsert($repo, ContributionType::Comment, 'comment:'.($comment['id'] ?? ''), $login, $this->userId($comment), $this->strv($comment, 'created_at'), $this->refFromUrl($this->strv($comment, 'issue_url'), 'issue'), [
                    'url' => $comment['html_url'] ?? null,
                    'kind' => 'issue',
                    'is_bot' => $this->isBot($login),
                ]);
            },
        );
    }

    private function backfillReviewComments(string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListPullRequestReviewComments($repo, $page, self::PER_PAGE),
            function (array $comment) use ($repo): void {
                $login = $this->login($comment);

                $this->upsert($repo, ContributionType::Comment, 'review_comment:'.($comment['id'] ?? ''), $login, $this->userId($comment), $this->strv($comment, 'created_at'), $this->refFromUrl($this->strv($comment, 'pull_request_url'), 'pr'), [
                    'url' => $comment['html_url'] ?? null,
                    'kind' => 'pr',
                    'is_bot' => $this->isBot($login),
                ]);
            },
        );
    }

    private function backfillCommits(string $repo): void
    {
        $this->paginate(
            fn (int $page): Request => new ListCommits($repo, $page, self::PER_PAGE),
            function (array $commit) use ($repo): void {
                $author = is_array($commit['author'] ?? null) ? $commit['author'] : [];
                $commitMeta = is_array($commit['commit'] ?? null) ? $commit['commit'] : [];
                $commitAuthor = is_array($commitMeta['author'] ?? null) ? $commitMeta['author'] : [];

                $login = isset($author['login']) && is_string($author['login'])
                    ? $author['login']
                    : (isset($commitAuthor['name']) && is_string($commitAuthor['name']) ? $commitAuthor['name'] : 'ghost');

                $actorId = isset($author['id']) && is_numeric($author['id']) ? (int) $author['id'] : null;
                $date = isset($commitAuthor['date']) && is_string($commitAuthor['date']) ? $commitAuthor['date'] : '';

                $this->upsert($repo, ContributionType::Commit, 'commit:'.($commit['sha'] ?? ''), $login, $actorId, $date, null, [
                    'url' => $commit['html_url'] ?? null,
                    'is_bot' => $this->isBot($login),
                ]);
            },
        );
    }

    /**
     * @param  callable(int): Request  $request
     * @param  callable(array<string, mixed>): void  $handle
     */
    private function paginate(callable $request, callable $handle): void
    {
        $page = 1;

        do {
            /** @var list<array<string, mixed>> $items */
            $items = (array) $this->github->send($request($page))->throw()->json();

            foreach ($items as $item) {
                $handle($item);
            }

            $page++;
        } while (count($items) === self::PER_PAGE);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function upsert(
        string $repo,
        ContributionType $type,
        string $externalRef,
        string $actorLogin,
        ?int $actorId,
        string $occurredAt,
        ?string $targetRef,
        array $metadata,
    ): void {
        $this->recorder->execute($repo, $type, $externalRef, $actorLogin, $actorId, $occurredAt, $targetRef, $metadata);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function login(array $payload): string
    {
        $user = $payload['user'] ?? null;

        return is_array($user) && isset($user['login']) && is_string($user['login']) ? $user['login'] : 'ghost';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function userId(array $payload): ?int
    {
        $user = $payload['user'] ?? null;

        return is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function strv(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }

    private function refFromUrl(string $url, string $prefix): ?string
    {
        return preg_match('~/(\d+)$~', $url, $matches) === 1 ? $prefix.':'.$matches[1] : null;
    }

    private function isBot(string $login): bool
    {
        return str_ends_with($login, '[bot]');
    }
}
