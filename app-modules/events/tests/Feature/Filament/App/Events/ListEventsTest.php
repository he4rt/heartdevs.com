<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Filament\App\EventModels\Pages\ListEventModels;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::User->value);
    actingAs(User::factory()->create());
    $this->tenant = Tenant::factory()->create();
    Filament::setTenant($this->tenant);

    $this->events = EventModel::factory()->count(10)
        ->afterCreating(function (EventModel $event): void {
            $attendees = User::factory()->count(4)->create();

            foreach ($attendees as $user) {
                $event->attendees()->attach($user->id, [
                    'status' => fake()->randomElement(AttendingStatusEnum::cases()),
                ]);
            }
        })
        ->create([
            'tenant_id' => $this->tenant->getKey(),
            'end_at' => Date::tomorrow(),
        ]);
});

it('should render', function (): void {
    livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
        ->assertOk();
});

it('should render events', function (): void {
    $this->events->each(function (EventModel $event): void {
        livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
            ->assertOk()
            ->assertSeeText($event->title)
            ->assertSeeText($event->location)
            ->assertSeeText(Date::parse($event->event_at)->format('d/m/Y'))
            ->assertSeeText(Date::parse($event->start_at)->format('H:i:s'))
            ->assertSeeText(Date::parse($event->end_at)->format('H:i:s'))
            ->assertSeeText($event->event_type->getLabel());
    });
});

it('should see Register or Join Waitlist based on status', function ($status, $text, $dontSeeText): void {
    $this->events->each(function (EventModel $event) use ($status): void {
        $attendeeIds = $event->attendees->pluck('id');
        $event->attendees()->updateExistingPivot(
            $attendeeIds,
            ['status' => $status]
        );
    });

    $this->events->fresh();
    livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
        ->assertOk()
        ->assertSeeText($text)
        ->assertDontSeeText($dontSeeText);
})->with([
    'attending' => [AttendingStatusEnum::Attending->value, 'Join', 'Join Waitlist'],
    'waitlist' => [AttendingStatusEnum::Waitlist->value, 'Join Waitlist', 'Join123'],
]);

it('should be able to participate to an event', function (): void {
    $event = $this->events->first();
    livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
        ->assertOk()
        ->call('attend', $event->getKey())
        ->assertNotified(
            Notification::make()
                ->success()
                ->body('Send Successfully'),
        );

    expect($event->attendees()->count())->toBe(5)
        ->and($event->attendees()->get()->last()->getKey())->toBe(auth()->user()->getKey());
});

it('should go to waitlist', function (): void {
    $event = $this->events->first();
    $attendeeIds = $event->attendees->pluck('id');

    $event->attendees()->updateExistingPivot(
        $attendeeIds,
        ['status' => AttendingStatusEnum::Waitlist],
    );

    livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
        ->assertOk()
        ->call('attend', $event->getKey())
        ->assertNotified(
            Notification::make()
                ->success()
                ->body('Send Successfully'),
        );

    expect($event->attendees()->count())->toBe(5)
        ->and($event->fresh()->waitlist_count)->toBe(1)
        ->and($event->participate(auth()->user()->id))->toBeTrue();
});

it('should be able to leave an event', function (): void {
    $event = $this->events->first();
    $event->attendees()->attach(
        auth()->user()->getKey(),
        ['status' => AttendingStatusEnum::Waitlist]
    );
    livewire(ListEventModels::class, ['tenant' => $this->tenant->slug])
        ->assertOk()
        ->call('leave', $event->getKey())
        ->assertNotified(Notification::make()
            ->success()
            ->body('Leaved Event Successfully')
            ->send());

    $event->refresh();
    expect($event->attendees()->count())->toBe(4)
        ->and($event->participate(auth()->user()->id))->tobeFalse();
});
