<?php

declare(strict_types=1);

use He4rt\IntegrationSlido\Stream\SlidoSubscriptionPlan;

test('conecta no evento que ganhou uma overlay aberta', function (): void {
    $plan = SlidoSubscriptionPlan::between(wanted: ['pub-5'], connected: []);

    expect($plan->opening)->toBe(['pub-5'])
        ->and($plan->closing)->toBeEmpty()
        ->and($plan->keeping)->toBeEmpty();
});

test('desconecta do evento que ficou sem overlay aberta', function (): void {
    $plan = SlidoSubscriptionPlan::between(wanted: [], connected: ['pub-5']);

    expect($plan->opening)->toBeEmpty()
        ->and($plan->closing)->toBe(['pub-5']);
});

test('troca de evento quando o link muda no painel e mantém o que continua igual', function (): void {
    $plan = SlidoSubscriptionPlan::between(wanted: ['pub-6', 'meetup'], connected: ['pub-5', 'meetup']);

    expect($plan->opening)->toBe(['pub-6'])
        ->and($plan->closing)->toBe(['pub-5'])
        ->and($plan->keeping)->toBe(['meetup']);
});
