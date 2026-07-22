<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support;

use He4rt\Community\Retrospective\DTOs\DeckConfig;

/**
 * Ponte entre o VO DeckConfig e os campos planos do formulário Filament. O form
 * não edita o VO direto (Filament trabalha com arrays): expande o VO em linhas de
 * repeater ao preencher e as recolhe de volta num payload de deck_config ao salvar
 * (o cast AsDeckConfig aceita array).
 */
final class DeckConfigForm
{
    /**
     * Linhas do repeater de fontes (ordem = posição; enabled = on/off). Fontes na
     * ordem curada primeiro, as demais fontes disponíveis em seguida.
     *
     * @return list<array{key: string, label: string, enabled: bool}>
     */
    public static function sourceRows(DeckConfig $config): array
    {
        $available = AvailableSources::map();

        $ordered = array_values(array_filter(
            $config->order,
            static fn (string $key): bool => isset($available[$key]),
        ));

        $rows = [];

        foreach ($ordered as $key) {
            $rows[] = ['key' => $key, 'label' => $available[$key], 'enabled' => $config->showsSource($key)];
        }

        foreach ($available as $key => $label) {
            if (!in_array($key, $ordered, strict: true)) {
                $rows[] = ['key' => $key, 'label' => $label, 'enabled' => $config->showsSource($key)];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{key: string, label: string, enabled: bool}>
     */
    public static function defaultSourceRows(): array
    {
        return self::sourceRows(new DeckConfig());
    }

    /**
     * @return list<array{source: string, ref: string}>
     */
    public static function exclusionRows(DeckConfig $config): array
    {
        $rows = [];

        foreach ($config->exclusions as $source => $refs) {
            foreach ($refs as $ref) {
                $rows[] = ['source' => $source, 'ref' => $ref];
            }
        }

        return $rows;
    }

    /**
     * Recolhe os campos planos do form num payload de deck_config. hidden_slides é
     * preservado do que já existia (o CRUD da Fase 2 não os edita; o Deck Builder
     * da Fase 3 os gerencia).
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $existingHiddenSlides
     * @return array<string, mixed>
     */
    public static function collapse(array $data, array $existingHiddenSlides): array
    {
        $order = [];
        $hiddenSources = [];

        foreach (self::rows($data['deck_sources'] ?? null) as $row) {
            $key = is_string($row['key'] ?? null) ? $row['key'] : null;

            if ($key === null) {
                continue;
            }

            $order[] = $key;

            if (($row['enabled'] ?? false) === false) {
                $hiddenSources[] = $key;
            }
        }

        $exclusions = [];

        foreach (self::rows($data['deck_exclusions'] ?? null) as $row) {
            $source = is_string($row['source'] ?? null) ? $row['source'] : null;
            $ref = is_string($row['ref'] ?? null) ? mb_trim($row['ref']) : '';
            if ($source === null) {
                continue;
            }
            if ($ref === '') {
                continue;
            }

            $exclusions[$source][] = $ref;
        }

        $data['deck_config'] = [
            'order' => $order,
            'hidden_sources' => $hiddenSources,
            'hidden_slides' => $existingHiddenSlides,
            'exclusions' => $exclusions,
        ];

        unset($data['deck_sources'], $data['deck_exclusions']);

        return $data;
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private static function rows(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
