<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Webhook;

use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;

/**
 * Projects a webhook event from the lake into github_contributions, scoped to the
 * allowlist, emitting GithubContributionRecorded for each row (the live seam).
 */
final readonly class ProjectGithubEvent
{
    public function __construct(
        private RecordContribution $recorder,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(string $event, array $payload): void
    {
        $repo = $this->str($payload, 'repository.full_name');

        if ($repo === '' || !$this->allowlisted($repo)) {
            return;
        }

        match ($event) {
            'pull_request' => $this->pullRequest($repo, $payload),
            'pull_request_review' => $this->review($repo, $payload),
            'issues' => $this->issue($repo, $payload),
            'issue_comment' => $this->issueComment($repo, $payload),
            'pull_request_review_comment' => $this->reviewComment($repo, $payload),
            'push' => $this->push($repo, $payload),
            default => null,
        };
    }

    private function allowlisted(string $repo): bool
    {
        return GithubRepository::query()->enabled()->where('full_name', $repo)->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pullRequest(string $repo, array $payload): void
    {
        $pr = $this->arr($payload, 'pull_request');
        $login = $this->str($pr, 'user.login', 'ghost');

        $this->recorder->execute($repo, ContributionType::Pr, 'pr:'.$this->str($pr, 'number'), $login, $this->intOrNull($pr, 'user.id'), $this->str($pr, 'created_at'), null, [
            'title' => data_get($pr, 'title'),
            'state' => data_get($pr, 'state'),
            'merged' => data_get($pr, 'merged_at') !== null,
            'url' => data_get($pr, 'html_url'),
            'additions' => $this->intOrNull($pr, 'additions') ?? 0,
            'deletions' => $this->intOrNull($pr, 'deletions') ?? 0,
            'changed_files' => $this->intOrNull($pr, 'changed_files') ?? 0,
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function review(string $repo, array $payload): void
    {
        $review = $this->arr($payload, 'review');
        $login = $this->str($review, 'user.login', 'ghost');

        $this->recorder->execute($repo, ContributionType::Review, 'review:'.$this->str($review, 'id'), $login, $this->intOrNull($review, 'user.id'), $this->str($review, 'submitted_at'), 'pr:'.$this->str($payload, 'pull_request.number'), [
            'state' => data_get($review, 'state'),
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issue(string $repo, array $payload): void
    {
        $issue = $this->arr($payload, 'issue');
        $login = $this->str($issue, 'user.login', 'ghost');

        $this->recorder->execute($repo, ContributionType::Issue, 'issue:'.$this->str($issue, 'number'), $login, $this->intOrNull($issue, 'user.id'), $this->str($issue, 'created_at'), null, [
            'title' => data_get($issue, 'title'),
            'state' => data_get($issue, 'state'),
            'url' => data_get($issue, 'html_url'),
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issueComment(string $repo, array $payload): void
    {
        $comment = $this->arr($payload, 'comment');
        $login = $this->str($comment, 'user.login', 'ghost');
        $isPr = data_get($payload, 'issue.pull_request') !== null;
        $target = ($isPr ? 'pr:' : 'issue:').$this->str($payload, 'issue.number');

        $this->recorder->execute($repo, ContributionType::Comment, 'comment:'.$this->str($comment, 'id'), $login, $this->intOrNull($comment, 'user.id'), $this->str($comment, 'created_at'), $target, [
            'url' => data_get($comment, 'html_url'),
            'kind' => $isPr ? 'pr' : 'issue',
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reviewComment(string $repo, array $payload): void
    {
        $comment = $this->arr($payload, 'comment');
        $login = $this->str($comment, 'user.login', 'ghost');

        $this->recorder->execute($repo, ContributionType::Comment, 'review_comment:'.$this->str($comment, 'id'), $login, $this->intOrNull($comment, 'user.id'), $this->str($comment, 'created_at'), 'pr:'.$this->str($payload, 'pull_request.number'), [
            'url' => data_get($comment, 'html_url'),
            'kind' => 'pr',
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function push(string $repo, array $payload): void
    {
        foreach ($this->arr($payload, 'commits') as $commit) {
            if (!is_array($commit)) {
                continue;
            }

            $username = $this->str($commit, 'author.username');
            $login = $username !== '' ? $username : $this->str($commit, 'author.name', 'ghost');

            $this->recorder->execute($repo, ContributionType::Commit, 'commit:'.$this->str($commit, 'id'), $login, null, $this->str($commit, 'timestamp'), null, [
                'url' => data_get($commit, 'url'),
                'is_bot' => str_ends_with($login, '[bot]'),
            ], emit: true);
        }
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function str(array $source, string $path, string $default = ''): string
    {
        $value = data_get($source, $path);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function intOrNull(array $source, string $path): ?int
    {
        $value = data_get($source, $path);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function arr(array $source, string $path): array
    {
        $value = data_get($source, $path);

        return is_array($value) ? $value : [];
    }
}
