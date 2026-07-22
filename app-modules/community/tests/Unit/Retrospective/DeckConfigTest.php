<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\DTOs\DeckConfig;

it('faz round-trip do payload sem perder curadoria', function (): void {
    $config = new DeckConfig(
        order: ['github', 'discord'],
        hiddenSources: ['discord'],
        hiddenSlides: ['github.repos'],
        exclusions: ['github' => ['pr:1', 'pr:2']],
    );

    $restored = DeckConfig::makeFromPayload($config->toArray());

    expect($restored)->toEqual($config);
});

it('usa defaults sãos com payload vazio', function (): void {
    $config = DeckConfig::makeFromPayload([]);

    expect($config->order)->toBeEmpty()
        ->and($config->hiddenSources)->toBeEmpty()
        ->and($config->exclusions)->toBeEmpty()
        ->and($config->showsSource('github'))->toBeTrue()
        ->and($config->showsSlide('github.repos'))->toBeTrue();
});

it('descarta tipos inválidos ao reidratar', function (): void {
    $config = DeckConfig::makeFromPayload([
        'order' => ['github', 42, null],
        'hidden_sources' => 'nope',
        'exclusions' => ['github' => ['pr:1', 99], 7 => ['x']],
    ]);

    expect($config->order)->toBe(['github'])
        ->and($config->hiddenSources)->toBeEmpty()
        ->and($config->exclusionsFor('github'))->toBe(['pr:1']);
});

it('responde aos helpers de visibilidade, posição e exclusions', function (): void {
    $config = new DeckConfig(
        order: ['github', 'discord'],
        hiddenSources: ['discord'],
        hiddenSlides: ['github.repos'],
        exclusions: ['github' => ['pr:1'], 'discord' => ['actor:x']],
    );

    expect($config->showsSource('discord'))->toBeFalse()
        ->and($config->showsSource('github'))->toBeTrue()
        ->and($config->showsSlide('github.repos'))->toBeFalse()
        ->and($config->position('discord'))->toBe(1)
        ->and($config->position('twitch'))->toBe(2)
        ->and($config->exclusionsFor('github'))->toBe(['pr:1'])
        ->and($config->allExclusions())->toEqualCanonicalizing(['pr:1', 'actor:x']);
});
