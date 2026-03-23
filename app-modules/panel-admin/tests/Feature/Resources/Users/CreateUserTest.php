<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Users\Pages\CreateUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    livewire(CreateUser::class)->assertOk();
});

it('can create a user', function (): void {
    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(User::class, [
        'username' => 'newuser',
        'name' => 'New User',
        'email' => 'test@example.com',
    ]);
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateUser::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'username is required' => ['username', '', 'required'],
    'username min 3' => ['username', 'ab', 'min'],
    'name is required' => ['name', '', 'required'],
]);
