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
        // Canonicaliza para minúsculas (igual ao cadastro) — sem isso, um repo com
        // case diferente no payload não casaria a allowlist e o evento seria perdido.
        $repo = mb_strtolower($this->str($payload, 'repository.full_name'));

        if ($repo === '') {
            return;
        }

        // Fan-out: cada comunidade que acompanha esse repo recebe a sua própria
        // contribuição (isolamento por tenant). Uma entrega vira N projeções.
        foreach ($this->tenantsTracking($repo) as $tenantId) {
            match ($event) {
                'pull_request' => $this->pullRequest($tenantId, $repo, $payload),
                'pull_request_review' => $this->review($tenantId, $repo, $payload),
                'issues' => $this->issue($tenantId, $repo, $payload),
                'issue_comment' => $this->issueComment($tenantId, $repo, $payload),
                'pull_request_review_comment' => $this->reviewComment($tenantId, $repo, $payload),
                'push' => $this->push($tenantId, $repo, $payload),
                default => null,
            };
        }
    }

    /**
     * @return list<string>
     */
    private function tenantsTracking(string $repo): array
    {
        return GithubRepository::query()
            ->enabled()
            ->where('full_name', $repo)
            ->pluck('tenant_id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pullRequest(string $tenantId, string $repo, array $payload): void
    {
        $pr = $this->arr($payload, 'pull_request');
        $login = $this->str($pr, 'user.login', 'ghost');

        $this->recorder->execute($tenantId, $repo, ContributionType::Pr, 'pr:'.$this->str($pr, 'number'), $login, $this->intOrNull($pr, 'user.id'), $this->str($pr, 'created_at'), null, [
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
    private function review(string $tenantId, string $repo, array $payload): void
    {
        $review = $this->arr($payload, 'review');
        $login = $this->str($review, 'user.login', 'ghost');

        $this->recorder->execute($tenantId, $repo, ContributionType::Review, 'review:'.$this->str($review, 'id'), $login, $this->intOrNull($review, 'user.id'), $this->str($review, 'submitted_at'), 'pr:'.$this->str($payload, 'pull_request.number'), [
            'state' => data_get($review, 'state'),
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issue(string $tenantId, string $repo, array $payload): void
    {
        $issue = $this->arr($payload, 'issue');
        $login = $this->str($issue, 'user.login', 'ghost');

        $this->recorder->execute($tenantId, $repo, ContributionType::Issue, 'issue:'.$this->str($issue, 'number'), $login, $this->intOrNull($issue, 'user.id'), $this->str($issue, 'created_at'), null, [
            'title' => data_get($issue, 'title'),
            'state' => data_get($issue, 'state'),
            'url' => data_get($issue, 'html_url'),
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issueComment(string $tenantId, string $repo, array $payload): void
    {
        $comment = $this->arr($payload, 'comment');
        $login = $this->str($comment, 'user.login', 'ghost');
        $isPr = data_get($payload, 'issue.pull_request') !== null;
        $target = ($isPr ? 'pr:' : 'issue:').$this->str($payload, 'issue.number');

        $this->recorder->execute($tenantId, $repo, ContributionType::Comment, 'comment:'.$this->str($comment, 'id'), $login, $this->intOrNull($comment, 'user.id'), $this->str($comment, 'created_at'), $target, [
            'url' => data_get($comment, 'html_url'),
            'kind' => $isPr ? 'pr' : 'issue',
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reviewComment(string $tenantId, string $repo, array $payload): void
    {
        $comment = $this->arr($payload, 'comment');
        $login = $this->str($comment, 'user.login', 'ghost');

        $this->recorder->execute($tenantId, $repo, ContributionType::Comment, 'review_comment:'.$this->str($comment, 'id'), $login, $this->intOrNull($comment, 'user.id'), $this->str($comment, 'created_at'), 'pr:'.$this->str($payload, 'pull_request.number'), [
            'url' => data_get($comment, 'html_url'),
            'kind' => 'pr',
            'is_bot' => str_ends_with($login, '[bot]'),
        ], emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function push(string $tenantId, string $repo, array $payload): void
    {
        foreach ($this->arr($payload, 'commits') as $commit) {
            if (!is_array($commit)) {
                continue;
            }

            $username = $this->str($commit, 'author.username');
            $login = $username !== '' ? $username : $this->str($commit, 'author.name', 'ghost');

            $this->recorder->execute($tenantId, $repo, ContributionType::Commit, 'commit:'.$this->str($commit, 'id'), $login, null, $this->str($commit, 'timestamp'), null, [
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
