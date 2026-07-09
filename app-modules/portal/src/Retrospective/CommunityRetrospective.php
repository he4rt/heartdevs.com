<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Support\Collection;

/**
 * Read model for the community presentation. Applies the "filter on read" rules
 * (decision #10): excludes only bots, counts by occurred_at within the period, and
 * groups by contributor login. Closed-unmerged PRs DO count as participation but are
 * broken out (prs_merged / prs_unmerged) so the view can distinguish their outcome.
 */
final readonly class CommunityRetrospective
{
    public function __construct(
        private string $tenantId,
        private RetrospectiveFilters $filters,
    ) {}

    /**
     * @return array{
     *     period: array{since: string, until: string},
     *     meta: array<string, int>,
     *     people: list<array<string, mixed>>,
     *     repos: list<array<string, mixed>>,
     *     highlights: list<array<string, mixed>>,
     * }
     */
    public function build(): array
    {
        /** @var Collection<int, GithubContribution> $contributions */
        $contributions = GithubContribution::query()
            ->where('tenant_id', $this->tenantId)
            ->whereBetween('occurred_at', [$this->filters->since, $this->filters->until])
            ->get()
            ->when(
                $this->filters->hideBots,
                fn (Collection $items): Collection => $items->reject(fn (GithubContribution $contribution): bool => $this->isBot($contribution)),
            )
            ->when(
                $this->filters->repos !== [],
                fn (Collection $items): Collection => $items->filter(fn (GithubContribution $contribution): bool => in_array($contribution->repo, $this->filters->repos, strict: true)),
            )
            ->filter(fn (GithubContribution $contribution): bool => in_array($contribution->type, $this->filters->types, strict: true))
            ->reject(fn (GithubContribution $contribution): bool => $this->filteredOutByOutcome($contribution))
            ->when(
                $this->filters->person !== null,
                fn (Collection $items): Collection => $items->filter(fn (GithubContribution $contribution): bool => $contribution->actor_login === $this->filters->person),
            )
            ->values();

        /** @var list<array<string, mixed>> $people */
        $people = $contributions
            ->groupBy('actor_login')
            ->map(fn (Collection $items, string $login): array => $this->person($login, $items))
            ->sortByDesc(fn (array $person): int => match ($this->filters->sort) {
                'prs' => (int) $person['prs'] * 1_000 + (int) $person['total'],
                'lines' => (int) $person['additions'] + (int) $person['deletions'],
                default => (int) $person['total'],
            })
            ->values()
            ->all();

        return [
            'period' => ['since' => $this->filters->since->toDateString(), 'until' => $this->filters->until->toDateString()],
            'meta' => [
                'people' => count($people),
                'prs' => $this->countType($contributions, ContributionType::Pr),
                'prs_merged' => $this->countMergedPrs($contributions),
                'prs_unmerged' => $this->countUnmergedPrs($contributions),
                'reviews' => $this->countType($contributions, ContributionType::Review),
                'issues' => $this->countType($contributions, ContributionType::Issue),
                'comments' => $this->countType($contributions, ContributionType::Comment),
                'review_comments' => $this->countType($contributions, ContributionType::ReviewComment),
                'commits' => $this->countType($contributions, ContributionType::Commit),
                'additions' => $this->sumMeta($contributions, 'additions'),
                'deletions' => $this->sumMeta($contributions, 'deletions'),
                'changed_files' => $this->sumMeta($contributions, 'changed_files'),
                'total' => $contributions->count(),
            ],
            'people' => $people,
            'repos' => $this->repos($contributions),
            'highlights' => $this->highlights($contributions),
        ];
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return array<string, mixed>
     */
    private function person(string $login, Collection $items): array
    {
        $actorId = $items->first()?->actor_id;

        return [
            'login' => $login,
            'avatar' => $this->avatar($login, $actorId),
            'url' => 'https://github.com/'.$login,
            'prs' => $this->countType($items, ContributionType::Pr),
            'prs_merged' => $this->countMergedPrs($items),
            'prs_unmerged' => $this->countUnmergedPrs($items),
            'reviews' => $this->countType($items, ContributionType::Review),
            'issues' => $this->countType($items, ContributionType::Issue),
            'comments' => $this->countType($items, ContributionType::Comment),
            'review_comments' => $this->countType($items, ContributionType::ReviewComment),
            'commits' => $this->countType($items, ContributionType::Commit),
            'additions' => $this->sumMeta($items, 'additions'),
            'deletions' => $this->sumMeta($items, 'deletions'),
            'pr_refs' => $this->prRefs($items),
            'issue_refs' => $this->issueRefs($items),
            'total' => $items->count(),
        ];
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countType(Collection $items, ContributionType $type): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $contribution->type === $type)->count();
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function sumMeta(Collection $items, string $key): int
    {
        return (int) $items->sum(static function (GithubContribution $contribution) use ($key): int {
            $metadata = $contribution->metadata ?? [];

            return (int) ($metadata[$key] ?? 0);
        });
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return list<array{num: int, title: string, url: string|null, state: string|null}>
     */
    private function prRefs(Collection $items): array
    {
        return array_values($items
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr)
            ->sortByDesc(fn (GithubContribution $contribution): mixed => $contribution->occurred_at)
            ->map(function (GithubContribution $contribution): array {
                $metadata = $contribution->metadata ?? [];
                $url = $metadata['url'] ?? null;
                $state = $metadata['state'] ?? null;

                return [
                    'num' => $this->refNumber($contribution->external_ref),
                    'title' => (string) ($metadata['title'] ?? ''),
                    'url' => is_string($url) ? $url : null,
                    'state' => is_string($state) ? $state : null,
                ];
            })
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     * @return list<array{num: int, title: string, url: string|null}>
     */
    private function issueRefs(Collection $items): array
    {
        return array_values($items
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Issue)
            ->sortByDesc(fn (GithubContribution $contribution): mixed => $contribution->occurred_at)
            ->map(function (GithubContribution $contribution): array {
                $metadata = $contribution->metadata ?? [];
                $url = $metadata['url'] ?? null;

                return [
                    'num' => $this->refNumber($contribution->external_ref),
                    'title' => (string) ($metadata['title'] ?? ''),
                    'url' => is_string($url) ? $url : null,
                ];
            })
            ->values()
            ->all());
    }

    private function refNumber(string $externalRef): int
    {
        $parts = explode(':', $externalRef);

        return (int) ($parts[1] ?? 0);
    }

    private function avatar(string $login, ?int $actorId): string
    {
        return $actorId !== null
            ? 'https://avatars.githubusercontent.com/u/'.$actorId.'?v=4'
            : 'https://github.com/'.$login.'.png';
    }

    private function filteredOutByOutcome(GithubContribution $contribution): bool
    {
        if ($this->filters->outcome === null || $contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];
        $merged = ($metadata['merged'] ?? false) === true;
        $state = $metadata['state'] ?? null;

        return match ($this->filters->outcome) {
            'merged' => !$merged,
            'open' => $state !== 'open',
            'closed' => $state !== 'closed' || $merged,
        };
    }

    /**
     * @return array{num: int, title: string, url: string|null, state: string|null, author_login: string, additions: int, deletions: int, changed_files: int}
     */
    private function prRow(GithubContribution $contribution): array
    {
        $metadata = $contribution->metadata ?? [];
        $url = $metadata['url'] ?? null;
        $state = $metadata['state'] ?? null;

        return [
            'num' => $this->refNumber($contribution->external_ref),
            'title' => (string) ($metadata['title'] ?? ''),
            'url' => is_string($url) ? $url : null,
            'state' => is_string($state) ? $state : null,
            'author_login' => $contribution->actor_login,
            'additions' => (int) ($metadata['additions'] ?? 0),
            'deletions' => (int) ($metadata['deletions'] ?? 0),
            'changed_files' => (int) ($metadata['changed_files'] ?? 0),
        ];
    }

    /**
     * @param  Collection<int, GithubContribution>  $contributions
     * @return list<array<string, mixed>>
     */
    private function repos(Collection $contributions): array
    {
        return array_values($contributions
            ->groupBy('repo')
            ->map(function (Collection $items, string $repo): array {
                $prs = $items->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr);

                return [
                    'full_name' => $repo,
                    'name' => explode('/', $repo)[1] ?? $repo,
                    'prs' => $prs
                        ->map(fn (GithubContribution $contribution): array => $this->prRow($contribution))
                        ->sortByDesc(fn (array $pr): int => $pr['additions'] + $pr['deletions'])
                        ->values()
                        ->all(),
                    'people' => $items
                        ->groupBy('actor_login')
                        ->map(fn (Collection $group, string $login): array => [
                            'login' => $login,
                            'avatar' => $this->avatar($login, $group->first()?->actor_id),
                        ])
                        ->values()
                        ->all(),
                    'metrics' => [
                        'prs' => $prs->count(),
                        'additions' => $this->sumMeta($prs, 'additions'),
                        'deletions' => $this->sumMeta($prs, 'deletions'),
                        'changed_files' => $this->sumMeta($prs, 'changed_files'),
                    ],
                ];
            })
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, GithubContribution>  $contributions
     * @return list<array<string, mixed>>
     */
    private function highlights(Collection $contributions): array
    {
        return array_values($contributions
            ->filter(fn (GithubContribution $contribution): bool => $contribution->type === ContributionType::Pr)
            ->map(fn (GithubContribution $contribution): array => [...$this->prRow($contribution), 'repo' => $contribution->repo])
            ->sortByDesc(fn (array $pr): int => $pr['additions'] + $pr['deletions'])
            ->take(4)
            ->values()
            ->all());
    }

    private function isBot(GithubContribution $contribution): bool
    {
        $metadata = $contribution->metadata ?? [];

        return ($metadata['is_bot'] ?? false) === true
            || str_ends_with($contribution->actor_login, '[bot]');
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countMergedPrs(Collection $items): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $this->isMergedPr($contribution))->count();
    }

    /**
     * @param  Collection<int, GithubContribution>  $items
     */
    private function countUnmergedPrs(Collection $items): int
    {
        return $items->filter(fn (GithubContribution $contribution): bool => $this->isUnmergedClosedPr($contribution))->count();
    }

    private function isMergedPr(GithubContribution $contribution): bool
    {
        if ($contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];

        return ($metadata['merged'] ?? false) === true;
    }

    private function isUnmergedClosedPr(GithubContribution $contribution): bool
    {
        if ($contribution->type !== ContributionType::Pr) {
            return false;
        }

        $metadata = $contribution->metadata ?? [];

        return ($metadata['state'] ?? null) === 'closed' && ($metadata['merged'] ?? false) !== true;
    }
}
