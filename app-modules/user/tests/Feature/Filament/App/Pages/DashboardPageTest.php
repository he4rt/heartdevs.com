<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Character\Models\Character;
use He4rt\Events\Models\EventModel;
use He4rt\Season\Models\Season;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Filament\User\Pages\UserDashboard;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->user = User::factory()->create();
    $tenant = Tenant::factory()
        ->for($this->user, 'owner')
        ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($this->user))
        ->create();

    actingAs($this->user);

    Filament::setTenant($tenant);

    $this->character = Character::factory()->create([
        'user_id' => $this->user->getKey(),
        'tenant_id' => $tenant->getKey(),
    ]);
    $this->events = EventModel::factory()->count(5)->create([
        'tenant_id' => $tenant->getKey(),
    ]);
    Season::factory()
        ->recycle($tenant)
        ->create([
            'name' => 'Season 1',
            'started_at' => now()->subMonth(),
            'ended_at' => today(),
        ]);
});

it('should render', function (): void {
    livewire(UserDashboard::class)
        ->assertOk();
});

it('should be able to see user experience/stats', function (): void {
    $nextLevelXp = $this->character->percentageExperience + $this->character->experience;

    livewire(UserDashboard::class)
        ->assertOk()
        ->assertSeeTextInOrder(['Level', $this->character->level])
        ->assertSeeTextInOrder(['Reputation', $this->character->reputation])
        ->assertSeeTextInOrder([$this->character->experience, '/', $nextLevelXp])
        ->assertSeeTextInOrder([(int) $this->character->experiencePercentageRemaining, '%', 'to next level']);
})->skip();

it('should be able to see events details', function (): void {
    $this->events->each(function (EventModel $event): void {
        livewire(UserDashboard::class)
            ->assertOk()
            ->assertSeeText($event->title)
            ->assertSeeText(Date::parse($event->starts_at)->format('d/m/Y H:i'))
            ->assertSeeText(Date::parse($event->ends_at)->format('d/m/Y H:i'));
    });
});
