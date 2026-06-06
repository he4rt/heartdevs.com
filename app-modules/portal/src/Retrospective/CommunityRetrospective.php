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
            ->values();

        /** @var list<array<string, mixed>> $people */
        $people = $contributions
            ->groupBy('actor_login')
            ->map(fn (Collection $items, string $login): array => $this->person($login, $items))
            ->sortByDesc('total')
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
                'commits' => $this->countType($contributions, ContributionType::Commit),
                'additions' => $this->sumMeta($contributions, 'additions'),
                'deletions' => $this->sumMeta($contributions, 'deletions'),
                'changed_files' => $this->sumMeta($contributions, 'changed_files'),
                'total' => $contributions->count(),
            ],
            'people' => $people,
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
            'avatar' => $actorId !== null
                ? 'https://avatars.githubusercontent.com/u/'.$actorId.'?v=4'
                : 'https://github.com/'.$login.'.png',
            'url' => 'https://github.com/'.$login,
            'prs' => $this->countType($items, ContributionType::Pr),
            'prs_merged' => $this->countMergedPrs($items),
            'prs_unmerged' => $this->countUnmergedPrs($items),
            'reviews' => $this->countType($items, ContributionType::Review),
            'issues' => $this->countType($items, ContributionType::Issue),
            'comments' => $this->countType($items, ContributionType::Comment),
            'commits' => $this->countType($items, ContributionType::Commit),
            'additions' => $this->sumMeta($items, 'additions'),
            'deletions' => $this->sumMeta($items, 'deletions'),
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
        return (int) $items->sum(function (GithubContribution $contribution) use ($key): int {
            $metadata = $contribution->metadata ?? [];

            return (int) ($metadata[$key] ?? 0);
        });
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
