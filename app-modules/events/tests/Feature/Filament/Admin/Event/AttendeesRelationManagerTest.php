<?php

declare(strict_types=1);

use Filament\Actions\DetachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\EditEvent;
use He4rt\Events\Filament\Admin\Resources\Events\RelationManagers\AttendeesRelationManager;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
    $this->tenant = Tenant::factory()->create();
    $this->event = EventModel::factory()->recycle($this->tenant)->create();
    Filament::setCurrentPanel('admin');
});

it('should render', function (): void {
    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])->assertOk();
});

it('should list event attendees', function (): void {
    $attendees = User::factory()->count(5)->create();

    foreach ($attendees as $attendee) {
        $this->event->attendees()->attach($attendee->getKey(), [
            'status' => AttendingStatusEnum::Attending,
        ]);
    }

    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($attendees)
        ->assertCountTableRecords(5);
});

it('should display attendee columns correctly', function (): void {
    $attendee = User::factory()->create([
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'is_donator' => true,
    ]);

    $this->event->attendees()->attach($attendee->getKey(), [
        'status' => AttendingStatusEnum::Attending,
    ]);

    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$attendee])
        ->assertTableColumnExists('username')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('is_donator')
        ->assertTableColumnExists('pivot.status');
});

it('should detach attendee using leave method', function (): void {
    $attendee = User::factory()->create();

    $this->event->attendees()->attach($attendee->getKey(), [
        'status' => AttendingStatusEnum::Attending,
    ]);

    $this->event->increment('attendees_count');

    expect($this->event->fresh()->attendees_count)->toBe(1)
        ->and($this->event->attendees()->count())->toBe(1);

    TestAction::make(DetachAction::class)->table();

    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$attendee])
        ->callTableAction('detach', $attendee);

    expect($this->event->fresh()->attendees_count)->toBe(0)
        ->and($this->event->attendees()->count())->toBe(0);
});

it('should decrement waitlist_count when detaching waitlisted attendee', function (): void {
    $attendee = User::factory()->create();

    $this->event->attendees()->attach($attendee->getKey(), [
        'status' => AttendingStatusEnum::Waitlist,
    ]);
    $this->event->increment('waitlist_count');

    expect($this->event->fresh()->waitlist_count)->toBe(1);

    TestAction::make(DetachAction::class)->table();

    livewire(AttendeesRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->callTableAction('detach', $attendee);

    expect($this->event->fresh()->waitlist_count)->toBe(0)
        ->and($this->event->attendees()->count())->toBe(0);
});
