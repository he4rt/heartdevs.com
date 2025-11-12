<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Filament\App\EventModels\Pages\ListEventModels;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::User->value);
    actingAs(User::factory()->create());
    $this->tenant = Tenant::factory()->create();
    Filament::setTenant($this->tenant);

    $this->events = EventModel::factory()->count(10)
        ->afterCreating(function (EventModel $event): void {
            $attendees = User::factory()->count(fake()->numberBetween(3, 10))->create();

            foreach ($attendees as $user) {
                $event->attendees()->attach($user->id, [
                    'status' => fake()->randomElement(AttendingStatusEnum::cases()),
                ]);
            }
        })
        ->create([
            'tenant_id' => $this->tenant->getKey(),
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
