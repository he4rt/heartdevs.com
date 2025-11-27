<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\EditFeedback;
use He4rt\Feedback\Models\Feedback;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('can delete feedback', function (): void {
    filament()->setCurrentPanel('admin');
    $feedback = Feedback::factory()->create();

    livewire(EditFeedback::class, [
        'record' => $feedback->getKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing(Feedback::class, [
        'id' => $feedback->getKey(),
    ]);
});
