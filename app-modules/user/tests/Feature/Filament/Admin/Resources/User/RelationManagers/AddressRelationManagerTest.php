<?php

declare(strict_types=1);

use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Filament\Admin\Resources\Users\RelationManagers\AddressRelationManager;
use He4rt\User\Models\Address;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
    Filament::setCurrentPanel('admin');
});

it('should render', function (): void {
    livewire(AddressRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk();
});

it('should list the related user address', function (): void {
    $address = Address::factory()
        ->recycle($this->user)
        ->create();

    livewire(AddressRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk()
        ->assertSee($address->country)
        ->assertSee($address->state)
        ->assertSee($address->zip_code);
});

it('should be able to register an address for an user', function (): void {
    $action = TestAction::make(CreateAction::class)->table();
    livewire(AddressRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk()
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm([
            'zip_code' => '091204200',
            'country' => 'BR',
            'state' => 'SP',
            'city' => 'tiradentes',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    /** @var Address $address */
    $address = auth()->user()->address;
    expect($address->country)->toBe('BR')
        ->and($address->state)->toBe('SP')
        ->and($address->city)->toBe('tiradentes')
        ->and($address->zip_code)->toBe('091204200');
});
