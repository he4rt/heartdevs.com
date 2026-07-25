<?php

declare(strict_types=1);

namespace He4rt\Community\Retrospective\DTOs;

use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

/**
 * Fotografia congelada dos SourceResult crus no momento do publish (jsonb via
 * AsRetrospectiveSnapshot). Os números ficam fixos aqui; a curadoria de
 * apresentação (DeckConfig) é aplicada por cima na composição, sem tocar as
 * fontes (ADR-0002). Slides voltam como FrozenSlide: o contrato (kind + props) é
 * tudo que a renderização precisa.
 */
final readonly class RetrospectiveSnapshot
{
    /**
     * @param  list<SourceResult>  $sources
     */
    public function __construct(
        public array $sources = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function makeFromPayload(array $payload): self
    {
        $sources = [];

        foreach ((array) ($payload['sources'] ?? []) as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $sources[] = new SourceResult(
                key: is_string($raw['key'] ?? null) ? $raw['key'] : '',
                label: is_string($raw['label'] ?? null) ? $raw['label'] : '',
                headline: self::headline($raw['headline'] ?? []),
                slides: self::slides($raw['slides'] ?? []),
            );
        }

        return new self($sources);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sources' => array_map(
                fn (SourceResult $source): array => [
                    'key' => $source->key,
                    'label' => $source->label,
                    'headline' => [
                        'metrics' => array_map(
                            fn (Metric $metric): array => ['label' => $metric->label, 'value' => $metric->value],
                            $source->headline->metrics,
                        ),
                    ],
                    'slides' => array_map(
                        fn (Slide $slide): array => ['kind' => $slide->kind(), 'props' => $slide->toArray()],
                        $source->slides,
                    ),
                ],
                $this->sources,
            ),
        ];
    }

    public function isEmpty(): bool
    {
        return $this->sources === [];
    }

    private static function headline(mixed $raw): HeadlineMetrics
    {
        $metrics = [];

        $entries = is_array($raw) && is_array($raw['metrics'] ?? null) ? $raw['metrics'] : [];

        foreach ($entries as $metric) {
            if (!is_array($metric)) {
                continue;
            }

            $label = $metric['label'] ?? null;
            $value = $metric['value'] ?? null;

            if (is_string($label) && (is_int($value) || is_string($value))) {
                $metrics[] = new Metric($label, (int) $value);
            }
        }

        return new HeadlineMetrics($metrics);
    }

    /**
     * @return list<Slide>
     */
    private static function slides(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $slides = [];

        foreach ($raw as $slide) {
            if (!is_array($slide)) {
                continue;
            }

            if (!isset($slide['kind'])) {
                continue;
            }

            $props = [];

            foreach ((array) ($slide['props'] ?? []) as $propKey => $propValue) {
                if (is_string($propKey)) {
                    $props[$propKey] = $propValue;
                }
            }

            $slides[] = new FrozenSlide((string) $slide['kind'], $props);
        }

        return $slides;
    }
}
