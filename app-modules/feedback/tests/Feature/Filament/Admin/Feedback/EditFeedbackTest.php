<?php

declare(strict_types=1);

use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\EditFeedback;
use He4rt\Feedback\Models\Feedback;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can load edit feedback page', function (): void {
    /** @var Feedback $feedback */
    $feedback = Feedback::factory()->create();

    livewire(EditFeedback::class, [
        'record' => $feedback->getKey(),
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'sender_id' => $feedback->sender_id,
            'target_id' => $feedback->target_id,
            'type' => $feedback->type,
            'message' => $feedback->message,
        ]);
});

it('can update feedback', function (): void {
    $feedback = Feedback::factory()->create();

    $newData = [
        'type' => 'compliment',
        'message' => 'Precisa melhorar',
    ];

    livewire(EditFeedback::class, [
        'record' => $feedback->getKey(),
    ])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Feedback::class, [
        'id' => $feedback->getKey(),
        'type' => $newData['type'],
        'message' => $newData['message'],
    ]);
});
