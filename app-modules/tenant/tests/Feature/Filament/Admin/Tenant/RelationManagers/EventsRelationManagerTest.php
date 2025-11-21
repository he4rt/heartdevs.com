<?php

declare(strict_types=1);

use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\RelationManagers\EventsRelationManager;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
    $this->tenant = Tenant::factory()->create();
    Filament::setCurrentPanel('admin');
});

it('should render', function (): void {
    livewire(EventsRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk();
});

it('should list the tenant events', function (): void {
    $events = EventModel::factory()->recycle($this->tenant)->count(5)->create();
    livewire(EventsRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk()
        ->assertCanSeeTableRecords($events)
        ->assertCountTableRecords($events->count());
});
it('should be able to create events for tenant ', function (): void {
    $action = TestAction::make(CreateAction::class)->table();
    livewire(EventsRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk()
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm([
            'title' => 'event title',
            'slug' => 'event-slug',
            'description' => 'event description',
            'location' => 'event location',
            'max_attendees' => 5,
            'event_type' => EventTypeEnum::Workshop->value,
            'active' => true,
            'event_at' => today(),
            'start_at' => today(),
            'end_at' => Date::tomorrow(),
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    /** @var EventModel $event */
    $event = $this->tenant->fresh()->events()->first();

    expect($event->title)->toBe('event title')
        ->and($event->slug)->toBe('event-slug')
        ->and($event->location)->toBe('event location')
        ->and($event->max_attendees)->toBe(5)
        ->and($event->event_type)->toBe(EventTypeEnum::Workshop)
        ->and($event->active)->toBeTrue();
});
