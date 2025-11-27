<?php

declare(strict_types=1);

use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\EditEvent;
use He4rt\Events\Filament\Admin\Resources\Events\RelationManagers\TalksRelationManager;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSubmission;
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
    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])->assertOk();
});

it('should list event talks', function (): void {
    $talks = EventSubmission::factory()
        ->recycle($this->event)
        ->recycle($this->tenant)
        ->count(5)
        ->create();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($talks)
        ->assertCountTableRecords(5);
});

it('should have create action', function (): void {
    $action = TestAction::make(CreateAction::class)->table();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertActionExists($action);
});

it('should create a talk for the event', function (): void {
    $speaker = User::factory()->create();
    $action = TestAction::make(CreateAction::class)->table();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->mountAction($action)
        ->fillForm([
            'user_id' => $speaker->getKey(),
            'tenant_id' => $this->tenant->getKey(),
            'title' => 'Introduction to Laravel',
            'description' => 'A beginner friendly talk about Laravel',
            'status' => TalkStatusEnum::Accepted->value,
            'field_type' => 'talk',
            'starts_at' => $this->event->start_at,
            'ends_at' => $this->event->start_at->addHour(),
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    $talk = $this->event->fresh()->talks()->first();

    expect($talk)
        ->title->toBe('Introduction to Laravel')
        ->user_id->toBe($speaker->getKey())
        ->event_id->toBe($this->event->getKey())
        ->tenant_id->toBe($this->tenant->getKey())
        ->status->toBe(TalkStatusEnum::Accepted);
});

it('should associate talk with event automatically', function (): void {
    $speaker = User::factory()->create();
    $action = TestAction::make(CreateAction::class)->table();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->mountAction($action)
        ->fillForm([
            'user_id' => $speaker->getKey(),
            'tenant_id' => $this->tenant->getKey(),
            'title' => 'My EventSubmission',
            'description' => '<p>EventSubmission description</p>',
            'status' => TalkStatusEnum::Pending->value,
            'field_type' => 'talk',
            'starts_at' => $this->event->start_at,
            'ends_at' => $this->event->start_at->addHour(),
        ])
        ->callMountedAction();

    $talk = EventSubmission::query()->first();

    expect($talk->event_id)->toBe($this->event->getKey());
});

it('should validate required fields when creating talk', function (): void {
    $action = TestAction::make(CreateAction::class)->table();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->mountAction($action)
        ->fillForm([
            'title' => '',
            'description' => '',
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['title', 'user_id']);
});

it('should list talks with different statuses', function (): void {
    $pendingTalk = EventSubmission::factory()
        ->recycle($this->event)
        ->recycle($this->tenant)
        ->create(['status' => TalkStatusEnum::Pending]);

    $acceptedTalk = EventSubmission::factory()
        ->recycle($this->event)
        ->recycle($this->tenant)
        ->create(['status' => TalkStatusEnum::Accepted]);

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$pendingTalk, $acceptedTalk])
        ->assertCountTableRecords(2);
});

it('should only show talks belonging to the event', function (): void {
    $eventTalks = EventSubmission::factory()
        ->recycle($this->event)
        ->recycle($this->tenant)
        ->count(3)
        ->create();

    $otherEvent = EventModel::factory()->recycle($this->tenant)->create();
    $otherTalks = EventSubmission::factory()
        ->recycle($otherEvent)
        ->recycle($this->tenant)
        ->count(2)
        ->create();

    livewire(TalksRelationManager::class, [
        'ownerRecord' => $this->event,
        'pageClass' => EditEvent::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($eventTalks)
        ->assertCanNotSeeTableRecords($otherTalks)
        ->assertCountTableRecords(3);
});
