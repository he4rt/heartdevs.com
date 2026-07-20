<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Portal\Retrospective\RetrospectiveDeck;

function fakeSource(string $key, string $label, bool $empty): RetrospectiveSource
{
    return new readonly class($key, $label, $empty) implements RetrospectiveSource
    {
        public function __construct(
            private string $sourceKey,
            private string $label,
            private bool $empty,
        ) {}

        public function key(): string
        {
            return $this->sourceKey;
        }

        public function collect(Period $period, SourceFilters $filters): SourceResult
        {
            return new SourceResult(
                $this->sourceKey,
                $this->label,
                new HeadlineMetrics($this->empty ? [] : [new Metric('Itens', 1)]),
            );
        }
    };
}

function composeWith(RetrospectiveSource ...$sources): array
{
    $period = Period::of(CarbonImmutable::parse('2026-06-01'), CarbonImmutable::parse('2026-06-07'));

    return new RetrospectiveDeck($sources)->compose($period, new SourceFilters());
}

it('ordena os blocos como github, depois discord, independentemente do registro', function (): void {
    $results = composeWith(
        fakeSource('discord', 'Discord', empty: false),
        fakeSource('github', 'GitHub', empty: false),
    );

    expect(array_map(fn (SourceResult $result): string => $result->key, $results))->toBe(['github', 'discord']);
});

it('joga fontes desconhecidas para o fim', function (): void {
    $results = composeWith(
        fakeSource('twitch', 'Twitch', empty: false),
        fakeSource('github', 'GitHub', empty: false),
    );

    expect(array_map(fn (SourceResult $result): string => $result->key, $results))->toBe(['github', 'twitch']);
});

it('descarta fontes sem dado no recorte', function (): void {
    $results = composeWith(
        fakeSource('github', 'GitHub', empty: false),
        fakeSource('discord', 'Discord', empty: true),
    );

    expect($results)->toHaveCount(1)
        ->and($results[0]->key)->toBe('github');
});
