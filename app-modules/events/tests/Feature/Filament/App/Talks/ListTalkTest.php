<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Filament\App\Talks\Pages\ListTalks;
use He4rt\Events\Models\EventSubmission;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::User->value);
    actingAs(User::factory()->create());
    $this->tenant = Tenant::factory()->create();
    Filament::setTenant($this->tenant);
    $this->talks = EventSubmission::factory()
        ->recycle($this->tenant)
        ->recycle(auth()->user())
        ->count(10)
        ->create();
});

it('should render', function (): void {
    livewire(ListTalks::class)
        ->assertOk();
});

it('should see all talks belongs to the user', function (): void {
    livewire(ListTalks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->talks)
        ->assertCountTableRecords($this->talks->count());
});
it('should see only talks that belongs to the user', function (): void {
    $anotherTalks = EventSubmission::factory()->for($this->tenant)->count(10)->create();
    livewire(ListTalks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->talks)
        ->assertCanNotSeeTableRecords($anotherTalks)
        ->assertCountTableRecords($this->talks->count());
});
it('should see only talks that belongs to the user and current tenant', function (): void {
    $anotherTenant = Tenant::factory()->create();
    $this->talks->each(function (EventSubmission $talk) use ($anotherTenant): void {
        $talk->update(['tenant_id' => $anotherTenant->getKey()]);
    });
    $this->talks->fresh();

    livewire(ListTalks::class)
        ->assertOk()
        ->assertCanNotSeeTableRecords($this->talks)
        ->assertCountTableRecords(0);

    Filament::setTenant($anotherTenant);
    livewire(ListTalks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->talks)
        ->assertCountTableRecords($this->talks->count());
});
