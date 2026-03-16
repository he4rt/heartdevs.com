<?php

declare(strict_types=1);

use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\CreateFeedback;
use He4rt\Feedback\Models\Feedback;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can create a feedback', function (): void {
    filament()->setCurrentPanel('admin');
    $sender = User::factory()->create();
    $target = User::factory()->create();

    $tenant = Tenant::factory()->create();

    $data = [
        'sender_id' => $sender->getKey(),
        'target_id' => $target->getKey(),
        'tenant_id' => $tenant->getKey(),
        'type' => 'compliment',
        'message' => 'Bom trabalho',
    ];

    livewire(CreateFeedback::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Feedback::class, $data);
});
