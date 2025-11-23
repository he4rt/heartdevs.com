<?php

declare(strict_types=1);

use He4rt\Message\Filament\Admin\Resources\Messages\Pages\CreateMessage;
use He4rt\Message\Models\Message;
use He4rt\Provider\Models\Provider;
use He4rt\Season\Models\Season;
use He4rt\Tenant\Models\Tenant;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can create a message', function (): void {
    $tenant = Tenant::factory()->create();
    $provider = Provider::factory()->create();
    $season = Season::factory()->create();

    $data = [
        'tenant_id' => $tenant->getKey(),
        'provider_id' => $provider->getKey(),
        'channel_id' => 1,
        'provider_message_id' => 'prov-msg-123',
        'content' => 'Mensagem de teste enviada',
        'sent_at' => now(),
        'obtained_experience' => 50,
    ];

    livewire(CreateMessage::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Message::class, [
        'tenant_id' => $tenant->getKey(),
        'provider_id' => $provider->getKey(),
        'content' => 'Mensagem de teste enviada',
    ]);
});
