<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use He4rt\Activity\Filament\Admin\Resources\Messages\Pages\EditMessage;
use He4rt\Activity\Models\Message;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('can delete a messages', function (): void {
    $message = Message::factory()->create();

    livewire(EditMessage::class, [
        'record' => $message->getKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing(Message::class, [
        'id' => $message->getKey(),
    ]);
});
