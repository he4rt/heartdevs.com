<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Webhook;

use He4rt\IntegrationGithub\Contributions\DTOs\NewContributionDTO;
use He4rt\IntegrationGithub\Contributions\RecordContribution;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Support\Str;

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
        $repo = Str::lower($this->stringFrom($payload, 'repository.full_name'));

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
        return array_values(GithubRepository::query()
            ->enabled()
            ->where('full_name', $repo)
            ->pluck('tenant_id')
            ->map(static fn (mixed $tenantId): string => (string) $tenantId)
            ->all());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function pullRequest(string $tenantId, string $repo, array $payload): void
    {
        $pr = $this->arrayFrom($payload, 'pull_request');
        $login = $this->stringFrom($pr, 'user.login', 'ghost');

        $this->recorder->execute(new NewContributionDTO(
            tenantId: $tenantId,
            repo: $repo,
            type: ContributionType::Pr,
            externalRef: ContributionType::Pr->ref($this->stringFrom($pr, 'number')),
            actorLogin: $login,
            actorId: $this->intFrom($pr, 'user.id'),
            occurredAt: $this->stringFrom($pr, 'created_at'),
            targetRef: null,
            metadata: [
                'title' => data_get($pr, 'title'),
                'state' => data_get($pr, 'state'),
                'merged' => data_get($pr, 'merged_at') !== null,
                'url' => data_get($pr, 'html_url'),
                'additions' => $this->intFrom($pr, 'additions') ?? 0,
                'deletions' => $this->intFrom($pr, 'deletions') ?? 0,
                'changed_files' => $this->intFrom($pr, 'changed_files') ?? 0,
                'is_bot' => $this->isBot($login),
            ],
        ), emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function review(string $tenantId, string $repo, array $payload): void
    {
        $review = $this->arrayFrom($payload, 'review');
        $submittedAt = $this->stringFrom($review, 'submitted_at');

        // Reviews PENDING (rascunho) não têm submitted_at e não são contribuição.
        if ($submittedAt === '') {
            return;
        }

        $login = $this->stringFrom($review, 'user.login', 'ghost');

        $this->recorder->execute(new NewContributionDTO(
            tenantId: $tenantId,
            repo: $repo,
            type: ContributionType::Review,
            externalRef: ContributionType::Review->ref($this->stringFrom($review, 'id')),
            actorLogin: $login,
            actorId: $this->intFrom($review, 'user.id'),
            occurredAt: $submittedAt,
            targetRef: ContributionType::Pr->ref($this->stringFrom($payload, 'pull_request.number')),
            metadata: [
                'state' => data_get($review, 'state'),
                'is_bot' => $this->isBot($login),
            ],
        ), emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issue(string $tenantId, string $repo, array $payload): void
    {
        $issue = $this->arrayFrom($payload, 'issue');
        $login = $this->stringFrom($issue, 'user.login', 'ghost');

        $this->recorder->execute(new NewContributionDTO(
            tenantId: $tenantId,
            repo: $repo,
            type: ContributionType::Issue,
            externalRef: ContributionType::Issue->ref($this->stringFrom($issue, 'number')),
            actorLogin: $login,
            actorId: $this->intFrom($issue, 'user.id'),
            occurredAt: $this->stringFrom($issue, 'created_at'),
            targetRef: null,
            metadata: [
                'title' => data_get($issue, 'title'),
                'state' => data_get($issue, 'state'),
                'url' => data_get($issue, 'html_url'),
                'is_bot' => $this->isBot($login),
            ],
        ), emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function issueComment(string $tenantId, string $repo, array $payload): void
    {
        $comment = $this->arrayFrom($payload, 'comment');
        $login = $this->stringFrom($comment, 'user.login', 'ghost');
        $isPr = data_get($payload, 'issue.pull_request') !== null;
        $target = ($isPr ? ContributionType::Pr : ContributionType::Issue)->ref($this->stringFrom($payload, 'issue.number'));

        $this->recorder->execute(new NewContributionDTO(
            tenantId: $tenantId,
            repo: $repo,
            type: ContributionType::Comment,
            externalRef: ContributionType::Comment->ref($this->stringFrom($comment, 'id')),
            actorLogin: $login,
            actorId: $this->intFrom($comment, 'user.id'),
            occurredAt: $this->stringFrom($comment, 'created_at'),
            targetRef: $target,
            metadata: [
                'url' => data_get($comment, 'html_url'),
                'kind' => $isPr ? 'pr' : 'issue',
                'is_bot' => $this->isBot($login),
            ],
        ), emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function reviewComment(string $tenantId, string $repo, array $payload): void
    {
        $comment = $this->arrayFrom($payload, 'comment');
        $login = $this->stringFrom($comment, 'user.login', 'ghost');

        $this->recorder->execute(new NewContributionDTO(
            tenantId: $tenantId,
            repo: $repo,
            type: ContributionType::ReviewComment,
            externalRef: ContributionType::ReviewComment->ref($this->stringFrom($comment, 'id')),
            actorLogin: $login,
            actorId: $this->intFrom($comment, 'user.id'),
            occurredAt: $this->stringFrom($comment, 'created_at'),
            targetRef: ContributionType::Pr->ref($this->stringFrom($payload, 'pull_request.number')),
            metadata: [
                'url' => data_get($comment, 'html_url'),
                'kind' => 'pr',
                'is_bot' => $this->isBot($login),
            ],
        ), emit: true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function push(string $tenantId, string $repo, array $payload): void
    {
        foreach ($this->arrayFrom($payload, 'commits') as $commit) {
            if (!is_array($commit)) {
                continue;
            }

            $username = $this->stringFrom($commit, 'author.username');
            $login = $username !== '' ? $username : $this->stringFrom($commit, 'author.name', 'ghost');

            $this->recorder->execute(new NewContributionDTO(
                tenantId: $tenantId,
                repo: $repo,
                type: ContributionType::Commit,
                externalRef: ContributionType::Commit->ref($this->stringFrom($commit, 'id')),
                actorLogin: $login,
                actorId: null,
                occurredAt: $this->stringFrom($commit, 'timestamp'),
                targetRef: null,
                metadata: [
                    'url' => data_get($commit, 'url'),
                    'is_bot' => $this->isBot($login),
                ],
            ), emit: true);
        }
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function stringFrom(array $source, string $path, string $default = ''): string
    {
        $value = data_get($source, $path);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function intFrom(array $source, string $path): ?int
    {
        $value = data_get($source, $path);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function arrayFrom(array $source, string $path): array
    {
        $value = data_get($source, $path);

        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function isBot(string $login): bool
    {
        return Str::endsWith($login, '[bot]');
    }
}
