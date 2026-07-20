<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Retrospective;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubCommunitySlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubCoreSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubHighlightsSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubPanoramaSlide;
use He4rt\IntegrationGithub\Retrospective\Slides\GithubRepoSlide;
use Illuminate\Support\Collection;

/**
 * Fonte GitHub da retrospectiva. Preserva 1:1 o cálculo do antigo read model do
 * portal (filtra bots, conta por occurred_at no período, agrupa por login,
 * quebra PRs merged/unmerged) e o empacota em slides tipados. Repos só entram
 * como card se tiverem PR no recorte; atividade só de review/issue/comentário
 * segue contando em meta/people/highlights.
 */
final class GithubSource implements RetrospectiveSource
{
    public function key(): string
    {
        return 'github';
    }

    public function collect(Period $period, SourceFilters $filters): SourceResult
    {
        /** @var Collection<int, GithubContribution> $contributions */
        $contributions = GithubContribution::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->get()
            ->when(
                $filters->hideBots,
                fn (Collection $items): Collection => $items->reject(fn (GithubContribution $contribution): bool => $this->isBot($contribution)),
            )
            ->values();

        if ($contributions->isEmpty()) {
            return new SourceResult($this->key(), 'GitHub', new HeadlineMetrics(), []);
        }

        /** @var list<array<string, mixed>> $people */
        $people = $contributions
            ->groupBy('actor_login')
            ->map(fn (Collection $items, string $login): array => $this->person($login, $items))
            ->sortByDesc(fn (array $person): int => (int) $person['total'])
            ->values()
            ->all();

        $repos = $this->repos($contributions);
        $highlights = $this->highlights($contributions);

        $meta = [
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
            // Repos exibidos = só os com PR no recorte (mesmo universo dos cards).
            'repos' => count($repos),
            'total' => $contributions->count(),
        ];

        return new SourceResult(
            key: $this->key(),
            label: 'GitHub',
            headline: $this->headline($meta),
            slides: $this->slides($meta, $repos, $highlights, $people),
        );
    }

    /**
     * @param  array<string, int>  $meta
     * @param  list<array<string, mixed>>  $repos
     * @param  list<array<string, mixed>>  $highlights
     * @param  list<array<string, mixed>>  $people
     * @return list<Slide>
     */
    private function slides(array $meta, array $repos, array $highlights, array $people): array
    {
        $slides = [new GithubPanoramaSlide($meta)];

        foreach ($repos as $i => $repo) {
            $slides[] = new GithubRepoSlide($repo, $i + 1);
        }

        if ($highlights !== []) {
            $slides[] = new GithubHighlightsSlide($highlights);
        }

        if ($people !== []) {
            $slides[] = new GithubCoreSlide($people);

            if (count($people) > 5) {
                $slides[] = new GithubCommunitySlide($people);
            }
        }

        return $slides;
    }

    /**
     * @param  array<string, int>  $meta
     */
    private function headline(array $meta): HeadlineMetrics
    {
        return new HeadlineMetrics([
            new Metric('Pessoas', $meta['people']),
            new Metric('PRs', $meta['prs']),
            new Metric('Reviews', $meta['reviews']),
            new Metric('Issues', $meta['issues']),
            new Metric('Commits', $meta['commits']),
            new Metric('Linhas', $meta['additions'] + $meta['deletions']),
        ]);
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
            // Só entram na retrospectiva repos com ao menos 1 PR no recorte;
            // atividade só de review/issue/comentário some do card (mas segue
            // contando em meta/people/highlights).
            ->filter(fn (array $repo): bool => $repo['metrics']['prs'] > 0)
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
