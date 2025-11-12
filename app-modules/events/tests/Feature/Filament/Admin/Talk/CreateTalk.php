<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\Talk;
use He4rt\User\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
    livewire(CreateTalk::class)
        ->assertOk();
});

it('should be able to register a talk', function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
    $event = EventModel::factory()->create();
    $user = User::factory()->create();
    livewire(CreateTalk::class)
        ->assertOk()
        ->fillForm([
            'tenant_id' => 1,
            'user_id' => $user->getKey(),
            'event_id' => $event->getKey(),
            'title' => 'talk title',
            'status' => TalkStatusEnum::Pending->value,
            'field_type' => 'some text right there',
            'description' => 'description you know',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Talk::class, 1);
    assertDatabaseHas(Talk::class, [
        'tenant_id' => 1,
        'user_id' => $user->getKey(),
        'event_id' => $event->getKey(),
        'title' => 'talk title',
        'status' => TalkStatusEnum::Pending->value,
        'field_type' => 'some text right there',
        'description' => '<p>description you know</p>',
    ]);
});
