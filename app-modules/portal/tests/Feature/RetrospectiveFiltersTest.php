<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\Portal\Retrospective\RetrospectiveFilters;

it('aplica defaults sensatos quando construído só com o período', function (): void {
    $filters = RetrospectiveFilters::period(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-07'),
    );

    expect($filters->repos)->toBeEmpty()
        ->and($filters->types)->toBe(ContributionType::cases())
        ->and($filters->outcome)->toBeNull()
        ->and($filters->person)->toBeNull()
        ->and($filters->hideBots)->toBeTrue()
        ->and($filters->sort)->toBe('total');
});

it('reconhece um tipo selecionado e ignora valores inválidos', function (): void {
    $filters = RetrospectiveFilters::make(
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-06-07'),
        types: ['pr', 'invalido', 'review'],
        outcome: 'banana',
        sort: 'lines',
    );

    expect($filters->types)->toBe([ContributionType::Pr, ContributionType::Review])
        ->and($filters->outcome)->toBeNull()
        ->and($filters->sort)->toBe('lines');
});
