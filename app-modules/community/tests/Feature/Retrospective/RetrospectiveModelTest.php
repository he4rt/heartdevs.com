<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\Retrospective\DTOs\DeckConfig;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\Community\Retrospective\Slides\FrozenSlide;

it('persiste e reidrata deck_config e snapshot como VOs tipados', function (): void {
    $config = new DeckConfig(
        order: ['github'],
        hiddenSources: ['discord'],
        exclusions: ['github' => ['pr:1']],
    );

    $snapshot = new RetrospectiveSnapshot([
        new SourceResult('github', 'GitHub', new HeadlineMetrics([new Metric('PRs', 3)]), [
            new FrozenSlide('github.panorama', ['meta' => ['prs' => 3]]),
        ]),
    ]);

    $retrospective = Retrospective::factory()->create([
        'deck_config' => $config,
        'snapshot' => $snapshot,
    ]);

    $fresh = $retrospective->fresh();

    expect($fresh->deck_config)->toBeInstanceOf(DeckConfig::class)
        ->and($fresh->deck_config->order)->toBe(['github'])
        ->and($fresh->deck_config->allExclusions())->toBe(['pr:1'])
        ->and($fresh->snapshot)->toBeInstanceOf(RetrospectiveSnapshot::class)
        ->and($fresh->snapshot->sources[0]->slides[0])->toBeInstanceOf(FrozenSlide::class)
        ->and($fresh->snapshot->sources[0]->slides[0]->kind())->toBe('github.panorama');
});

it('deixa o snapshot nulo enquanto rascunho, com deck_config sempre presente', function (): void {
    $retrospective = Retrospective::factory()->create()->fresh();

    expect($retrospective->snapshot)->toBeNull()
        ->and($retrospective->status)->toBe(RetrospectiveStatus::Draft)
        ->and($retrospective->deck_config)->toBeInstanceOf(DeckConfig::class);
});

it('projeta period e filters a partir das colunas', function (): void {
    $retrospective = Retrospective::factory()->create([
        'since' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'until' => CarbonImmutable::parse('2026-06-30 23:59:59'),
        'hide_bots' => false,
        'deck_config' => new DeckConfig(exclusions: ['github' => ['pr:9']]),
    ]);

    expect($retrospective->period()->since->toDateString())->toBe('2026-06-01')
        ->and($retrospective->period()->until->toDateString())->toBe('2026-06-30')
        ->and($retrospective->filters()->hideBots)->toBeFalse()
        ->and($retrospective->filters()->exclusions)->toBe(['pr:9']);
});

it('scopePublished traz só as publicadas', function (): void {
    Retrospective::factory()->create();
    $published = Retrospective::factory()->published()->create();

    expect(Retrospective::query()->published()->pluck('id')->all())->toBe([$published->id]);
});
