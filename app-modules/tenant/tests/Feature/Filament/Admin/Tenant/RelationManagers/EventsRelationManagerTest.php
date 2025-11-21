<?php

declare(strict_types=1);

use App\Filament\Shared\RelationManagers\EventsRelationManager;
use Filament\Facades\Filament;
use He4rt\Events\Models\EventModel;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

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
