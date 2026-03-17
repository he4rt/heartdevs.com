<?php

declare(strict_types=1);

use He4rt\Activity\Filament\Admin\Resources\Messages\Pages\ListMessages;
use He4rt\Activity\Models\Message;

use function Pest\Livewire\livewire;

it('renders the list of messages', function (): void {
    $messages = Message::factory()->count(5)->create();

    livewire(ListMessages::class)
        ->assertOk()
        ->assertCanSeeTableRecords($messages);
});
