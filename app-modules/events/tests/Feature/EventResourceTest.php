<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\CreateEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\EditEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\ListEvents;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $admin = User::factory()->create(['username' => 'events-test-admin']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($admin);

    config(['he4rt.admins' => 'events-test-admin']);
    $this->actingAs($admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
});

test('events list page renders successfully', function (): void {
    livewire(ListEvents::class)
        ->assertSuccessful();
});

test('events list shows existing events', function (): void {
    $event = Event::factory()->create(['title' => 'He4rt Meetup #42']);

    livewire(ListEvents::class)
        ->assertSee($event->title);
});

test('create event page renders successfully', function (): void {
    livewire(CreateEvent::class)
        ->assertSuccessful();
});

test('edit event page renders successfully', function (): void {
    $event = Event::factory()->create();

    livewire(EditEvent::class, ['record' => $event->getRouteKey()])
        ->assertSuccessful();
});

test('event resource points to the correct model', function (): void {
    expect(EventResource::getModel())->toBe(Event::class);
});
