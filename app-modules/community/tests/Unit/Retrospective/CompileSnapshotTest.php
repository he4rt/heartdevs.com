<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Actions\CompileSnapshot;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

function retroFakeSource(string $key, string $label, bool $empty): RetrospectiveSource
{
    return new readonly class($key, $label, $empty) implements RetrospectiveSource
    {
        public function __construct(
            private string $sourceKey,
            private string $sourceLabel,
            private bool $empty,
        ) {}

        public function key(): string
        {
            return $this->sourceKey;
        }

        public function label(): string
        {
            return $this->sourceLabel;
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult(
                $this->sourceKey,
                $this->sourceLabel,
                new HeadlineMetrics($this->empty ? [] : [new Metric('Itens', 1)]),
                $this->empty ? [] : [new FrozenSlide($this->sourceKey.'.panel', ['n' => 1])],
            );
        }
    };
}

function retroPeriod(): Period
{
    return Period::of(CarbonImmutable::parse('2026-06-01'), CarbonImmutable::parse('2026-06-30'));
}

it('coleta as fontes e empacota os SourceResult crus', function (): void {
    $snapshot = new CompileSnapshot([
        retroFakeSource('github', 'GitHub', empty: false),
        retroFakeSource('discord', 'Discord', empty: false),
    ])->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->sources)->toHaveCount(2)
        ->and(array_map(fn (SourceResult $source): string => $source->key, $snapshot->sources))
        ->toBe(['github', 'discord']);
});

it('descarta fontes sem dado no recorte', function (): void {
    $snapshot = new CompileSnapshot([
        retroFakeSource('github', 'GitHub', empty: false),
        retroFakeSource('discord', 'Discord', empty: true),
    ])->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->sources)->toHaveCount(1)
        ->and($snapshot->sources[0]->key)->toBe('github');
});

it('devolve snapshot vazio quando não há fontes', function (): void {
    $snapshot = new CompileSnapshot([])->execute(retroPeriod(), new SourceFilters());

    expect($snapshot->isEmpty())->toBeTrue();
});
