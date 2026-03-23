<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\EditUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertOk();
});

it('can update a user', function (): void {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'username' => 'updated-user',
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->username->toBe('updated-user')
        ->name->toBe('Updated Name');
});
