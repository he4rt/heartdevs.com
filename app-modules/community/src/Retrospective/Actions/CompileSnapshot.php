<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\Actions;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceFilters;

/**
 * Coleta todas as fontes registradas para um Period + filtros e empacota o
 * resultado cru num RetrospectiveSnapshot. É o que o publish congela.
 *
 * Não ordena nem cura: a ordem e o on/off vivem no DeckConfig e são aplicados
 * depois pelo ComposeDeck. Só descarta fontes vazias (não há o que congelar).
 * Adicionar uma fonte não toca esta classe: basta a tag "retrospective.source".
 */
final readonly class CompileSnapshot
{
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

    public function execute(Period $period, SourceFilters $filters): RetrospectiveSnapshot
    {
        $results = [];

        foreach ($this->sources as $source) {
            $result = $source->collect($period, $filters);

            if (!$result->isEmpty()) {
                $results[] = $result;
            }
        }

        return new RetrospectiveSnapshot($results);
    }
}
