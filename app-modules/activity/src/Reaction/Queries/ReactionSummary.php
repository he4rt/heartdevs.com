<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\Queries;

use He4rt\Activity\Reaction\DTOs\TimelineReactionSummary;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use Illuminate\Support\Collection;
use UnexpectedValueException;

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

        // toBase(): a linha é um agregado, não um UserReaction — hidratar o model
        // aqui produziria registros parciais (sem id) fáceis de confundir com
        // linhas reais.
        $rows = UserReaction::query()
            ->toBase()
            ->select(['timeline_id', 'reaction'])
            ->selectRaw('count(*) as total')
            ->whereIn('timeline_id', $ids)
            ->groupBy('timeline_id', 'reaction')
            ->get();

        foreach ($rows as $row) {
            $reaction = TimelineReaction::from($this->stringOf($row->reaction));

            $counts[$this->stringOf($row->timeline_id)][$reaction->value] = $this->countOf($row->total);
        }

        /** @var array<string, TimelineReaction> $mine */
        $mine = [];

        if ($userId !== null) {
            $mine = UserReaction::query()
                ->toBase()
                ->select(['timeline_id', 'reaction'])
                ->whereIn('timeline_id', $ids)
                ->where('user_id', $userId)
                ->get()
                ->mapWithKeys(fn ($row): array => [
                    $this->stringOf($row->timeline_id) => TimelineReaction::from(
                        $this->stringOf($row->reaction)
                    ),
                ])
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

    /**
     * Linhas de agregado chegam sem tipo; as colunas lidas aqui são NOT NULL
     * no schema, então qualquer outra coisa é um bug e não um caso a tratar.
     */
    private function stringOf(mixed $value): string
    {
        return is_string($value)
            ? $value
            : throw new UnexpectedValueException('Esperava string na linha do agregado de reações.');
    }

    private function countOf(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
