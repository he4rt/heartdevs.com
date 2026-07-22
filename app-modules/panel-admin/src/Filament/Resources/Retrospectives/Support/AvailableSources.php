<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;

/**
 * Descobre as fontes registradas (tag "retrospective.source") para o CRUD listar
 * e ordenar por (key, label) sem coletar dado. É a única razão de o contrato
 * expor label() estático.
 */
final class AvailableSources
{
    /**
     * @return array<string, string> key => label
     */
    public static function map(): array
    {
        $map = [];

        /** @var iterable<RetrospectiveSource> $sources */
        $sources = app()->tagged('retrospective.source');

        foreach ($sources as $source) {
            $map[$source->key()] = $source->label();
        }

        return $map;
    }
}
