<?php

declare(strict_types=1);

use He4rt\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

test('can create badge', function (): void {
    $tenant = Tenant::factory()->withDiscordProvider()->create();
    $payload = [
        'provider' => 'twitch',
        'name' => 'Aula foda',
        'description' => 'aula foda do dia foda',
        'redeem_code' => '123',
        'active' => true,
        'tenant_id' => $tenant->getKey(),
    ];

    $this->actingAsAdmin()
        ->postJson(route('badges.store'), $payload)
        ->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('badges', $payload);
});
