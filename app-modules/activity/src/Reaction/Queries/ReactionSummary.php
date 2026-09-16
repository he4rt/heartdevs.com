<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Queries;

use He4rt\Activity\Reaction\DTOs\TimelineReactionSummary;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use Illuminate\Support\Collection;

/**
 * Resume as reações de um conjunto de posts da timeline web em número fixo de
 * consultas: uma agregação `group by (timeline_id, reaction)` para as
 * contagens e, quando há usuário, uma leitura das linhas dele nos mesmos
 * posts. O custo não cresce com o tamanho da página.
 *
 * Lê só `activity_user_reactions`; o agregado do Discord (`activity_reactions`)
 * nunca entra na soma.
 */
final readonly class ReactionSummary
{
    /**
     * @param  iterable<array-key, string>  $timelineIds
     * @return Collection<string, TimelineReactionSummary> indexada por `timeline_id`, com um item para cada id pedido
     */
    public function forTimelines(iterable $timelineIds, ?string $userId): Collection
    {
        /** @var list<string> $ids */
        $ids = Collection::make($timelineIds)->unique()->values()->all();

        if ($ids === []) {
            return Collection::make();
        }

        /** @var array<string, array<string, int>> $counts */
        $counts = [];

        $rows = UserReaction::query()
            ->select(['timeline_id', 'reaction'])
            ->selectRaw('count(*) as total')
            ->whereIn('timeline_id', $ids)
            ->groupBy('timeline_id', 'reaction')
            ->get();

        foreach ($rows as $row) {
            $counts[$row->timeline_id][$row->reaction->value] = (int) $row->getAttribute('total');
        }

        /** @var array<string, TimelineReaction> $mine */
        $mine = [];

        if ($userId !== null) {
            $mine = UserReaction::query()
                ->whereIn('timeline_id', $ids)
                ->where('user_id', $userId)
                ->get(['timeline_id', 'reaction'])
                ->mapWithKeys(static fn (UserReaction $row): array => [$row->timeline_id => $row->reaction])
                ->all();
        }

        return Collection::make($ids)->mapWithKeys(
            static fn (string $id): array => [$id => new TimelineReactionSummary(
                timelineId: $id,
                counts: $counts[$id] ?? [],
                mine: $mine[$id] ?? null,
            )],
        );
    }
}
