<?php

declare(strict_types=1);

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\RelationManagers\MembersRelationManager;
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
it('should be able to attach members at tenant ', function (): void {
    $action = TestAction::make(AttachAction::class)->table();
    $member = User::factory()->create();
    livewire(MembersRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
        ->assertOk()
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm([
            'recordId' => $member->getKey(),
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    /** @var User $member */
    $newMember = $this->tenant->fresh()->members()->first();

    expect($newMember->name)->toBe($member->name)
        ->and($newMember->username)->toBe($member->username)
        ->and($newMember->email)->toBe($member->email)
        ->and($newMember->is_donator)->toBe($member->is_donator);
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
