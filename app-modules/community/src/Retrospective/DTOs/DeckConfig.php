<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

/**
 * Curadoria de APRESENTAÇÃO de uma retrospectiva, guardada na edição (jsonb via
 * AsDeckConfig). Separada do snapshot congelado: mexer aqui em ordem/on-off
 * re-deriva do snapshot sem recomputar números (garantia editorial do ADR-0002).
 *
 * Exceção: exclusions mexem no DADO (entram no SourceFilters do collect, ADR-0001),
 * então alterá-las exige recompilar o snapshot — não são reaplicadas na composição.
 */
final readonly class DeckConfig
{
    /**
     * @param  list<string>  $order  keys das fontes na ordem de exibição; fontes fora da lista vão para o fim
     * @param  list<string>  $hiddenSources  keys de fonte ocultadas do deck
     * @param  list<string>  $hiddenSlides  kinds de slide ocultados (ex.: "github.repos")
     * @param  array<string, list<string>>  $exclusions  refs escondidos por key de fonte (ex.: ["github" => ["pr:142"]])
     */
    public function __construct(
        public array $order = [],
        public array $hiddenSources = [],
        public array $hiddenSlides = [],
        public array $exclusions = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): self
    {
        $exclusions = [];

        foreach ((array) ($payload['exclusions'] ?? []) as $key => $refs) {
            if (is_string($key)) {
                $exclusions[$key] = self::stringList($refs);
            }
        }

        return new self(
            order: self::stringList($payload['order'] ?? []),
            hiddenSources: self::stringList($payload['hidden_sources'] ?? []),
            hiddenSlides: self::stringList($payload['hidden_slides'] ?? []),
            exclusions: $exclusions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'hidden_sources' => $this->hiddenSources,
            'hidden_slides' => $this->hiddenSlides,
            'exclusions' => $this->exclusions,
        ];
    }

    public function showsSource(string $key): bool
    {
        return !in_array($key, $this->hiddenSources, strict: true);
    }

    public function showsSlide(string $kind): bool
    {
        return !in_array($kind, $this->hiddenSlides, strict: true);
    }

    /**
     * Posição de uma fonte na ordem editorial; fontes fora da lista vão para o fim.
     */
    public function position(string $key): int
    {
        $index = array_search($key, $this->order, strict: true);

        return $index === false ? count($this->order) : $index;
    }

    /**
     * @return list<string>
     */
    public function exclusionsFor(string $key): array
    {
        return $this->exclusions[$key] ?? [];
    }

    /**
     * Todos os refs excluídos, achatados, para montar o SourceFilters do collect.
     *
     * @return list<string>
     */
    public function allExclusions(): array
    {
        $refs = [];

        foreach ($this->exclusions as $sourceRefs) {
            foreach ($sourceRefs as $ref) {
                $refs[] = $ref;
            }
        }

        return array_values(array_unique($refs));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
