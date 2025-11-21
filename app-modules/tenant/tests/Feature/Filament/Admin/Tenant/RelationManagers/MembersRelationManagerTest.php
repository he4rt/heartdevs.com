<?php

declare(strict_types=1);

use App\Filament\Shared\RelationManagers\MembersRelationManager;

use Filament\Actions\DetachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
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
    livewire(MembersRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk();
});

it('should list the tenant members', function (): void {
    $users = User::factory()->count(5)->create();
    $this->tenant->members()->attach($users->pluck('id'));
    livewire(MembersRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk()
        ->assertCanSeeTableRecords($users)
        ->assertCountTableRecords($users->count());
});
it('should be able to detach an user', function (): void {
    $users = User::factory()->count(5)->create();
    $this->tenant->members()->attach($users->pluck('id'));

    $action = TestAction::make(DetachAction::class)->table();
    livewire(MembersRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk()
        ->selectTableRecords($users->pluck('id')->toArray())
        ->callAction($action->bulk())
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);
});
