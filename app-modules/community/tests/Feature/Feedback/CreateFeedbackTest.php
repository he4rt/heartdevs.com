<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

test('can create', function (): void {

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            ExternalIdentity::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'external_account_id' => '123',
            ])->create();
        })
        ->create();

    $providerSender = ExternalIdentity::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);
    $providerTarget = ExternalIdentity::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);

    $payload = [
        'sender_id' => $providerSender->external_account_id,
        'target_id' => $providerTarget->external_account_id,
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
