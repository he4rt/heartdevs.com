<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Character\Models\Character;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Filament\Admin\Resources\Users\RelationManagers\CharacterRelationManager;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
    Filament::setCurrentPanel('admin');
});
it('should render', function (): void {
    livewire(CharacterRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk();
});

it('should list user characters', function (): void {
    $characters = Character::factory()
        ->recycle($this->user)
        ->count(2)
        ->create();
    livewire(CharacterRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk()
        ->assertCanSeeTableRecords($characters);
});
