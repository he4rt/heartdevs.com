<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Filament\App\Talks\Pages\ListTalks;
use He4rt\Events\Models\Talk;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::User->value);
    actingAs(User::factory()->create());
    $this->tenant = Tenant::factory()->create();
    Filament::setTenant($this->tenant);
    $this->talks = Talk::factory()->count(10)->recycle($this->tenant)->create();
});

it('should render', function (): void {
    livewire(ListTalks::class)
        ->assertOk();
});

it('should see all talks', function (): void {
    livewire(ListTalks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->talks)
        ->assertCountTableRecords($this->talks->count());
});
