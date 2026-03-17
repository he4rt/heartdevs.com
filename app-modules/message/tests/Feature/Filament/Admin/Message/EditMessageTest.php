<?php

declare(strict_types=1);

use He4rt\Gamification\Season\Models\Season;
use He4rt\Message\Filament\Admin\Resources\Messages\Pages\EditMessage;
use He4rt\Message\Models\Message;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can update a message', function (): void {
    $message = Message::factory()->create();
    $season = Season::factory()->create();

    $newData = [
        'content' => 'Conteúdo atualizado',
        'obtained_experience' => 200,
    ];

    livewire(EditMessage::class, [
        'record' => $message->getKey(),
    ])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Message::class, [
        'id' => $message->getKey(),
        'content' => 'Conteúdo atualizado',
        'obtained_experience' => 200,
    ]);
});

it('can load the edit page', function (): void {
    $message = Message::factory()->create();

    livewire(EditMessage::class, [
        'record' => $message->getKey(),
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'provider_id' => $message->provider_id,
            'channel_id' => $message->channel_id,
            'content' => $message->content,
            'provider_message_id' => $message->provider_message_id,
        ]);
});
