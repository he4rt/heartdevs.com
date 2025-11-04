<?php

declare(strict_types=1);

use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

test('can create', function (): void {

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            Provider::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'provider_id' => '123',
            ])->create();
        })
        ->create();

    $providerSender = Provider::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);
    $providerTarget = Provider::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);

    $payload = [
        'sender_id' => $providerSender->provider_id,
        'target_id' => $providerTarget->provider_id,
        'message' => 'mt legal vc',
        'type' => 'elogio',
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('feedbacks.create'), $payload)
        ->assertStatus(Response::HTTP_CREATED);

    $payload['sender_id'] = $providerSender->user->id;
    $payload['target_id'] = $providerTarget->user->id;
    $this->assertDatabaseHas('feedbacks', $payload);
});
