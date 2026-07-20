<?php

declare(strict_types=1);

namespace He4rt\Portal\Retrospective;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;

/**
 * Orquestrador da retrospectiva ao vivo. Resolve as fontes descobertas por tag,
 * coleta cada uma para o período e devolve os blocos ordenados, descartando as
 * fontes sem dado no recorte. Adicionar uma fonte não toca esta classe: basta a
 * fonte se registrar na tag "retrospective.source".
 *
 * A ordem de apresentação vive aqui na Fase 1; na Fase 2 ela migra para a
 * deck_config editorial da edição persistida.
 */
final readonly class RetrospectiveDeck
{
    /**
     * Ordem dos blocos por key de fonte; fontes fora da lista vão para o fim.
     *
     * @var list<string>
     */
    private const array ORDER = ['github', 'discord'];

    /** @var list<RetrospectiveSource> */
    private array $sources;

    /**
     * @param  iterable<RetrospectiveSource>  $sources
     */
    public function __construct(iterable $sources)
    {
        $this->sources = array_values(
            is_array($sources) ? $sources : iterator_to_array($sources, preserve_keys: false),
        );
    }

    /**
     * @return list<SourceResult>
     */
    public function compose(Period $period, SourceFilters $filters): array
    {
        $results = [];

        foreach ($this->sources as $source) {
            $result = $source->collect($period, $filters);

            if (!$result->isEmpty()) {
                $results[] = $result;
            }
        }

        usort(
            $results,
            fn (SourceResult $a, SourceResult $b): int => $this->position($a->key) <=> $this->position($b->key),
        );

        return $results;
    }

    private function position(string $key): int
    {
        $index = array_search($key, self::ORDER, strict: true);

        return $index === false ? count(self::ORDER) : $index;
    }
}
