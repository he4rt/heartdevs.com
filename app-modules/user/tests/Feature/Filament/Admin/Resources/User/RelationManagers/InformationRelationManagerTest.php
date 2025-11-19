<?php

declare(strict_types=1);

use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\User\Filament\Admin\Resources\Users\Pages\EditUser;
use He4rt\User\Filament\Admin\Resources\Users\RelationManagers\InformationRelationManager;
use He4rt\User\Models\Information;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
    Filament::setCurrentPanel('admin');
});
it('should render', function (): void {
    livewire(InformationRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk();
});

it('should list user information', function (): void {
    $information = Information::factory()
        ->recycle($this->user)
        ->create();
    livewire(InformationRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk()
        ->assertSee($information->github_url)
        ->assertSee($information->linkedin_url)
        ->assertSee($information->birthdate)
        ->assertSee($information->nickname);
});
it('should be able to register information about the user', function (): void {
    $action = TestAction::make(CreateAction::class)->table();
    livewire(InformationRelationManager::class, ['ownerRecord' => $this->user, 'pageClass' => EditUser::class])
        ->assertOk()
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm([
            'nickname' => 'nickname',
            'github_url' => 'https://github.com/johndoe',
            'linkedin_url' => 'https://linkedin.com/in/johndoe',
            'birthdate' => '2000-01-01',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    $information = auth()->user()->information;
    expect($information->nickname)->toBe('nickname')
        ->and($information->github_url)->toBe('https://github.com/johndoe')
        ->and($information->linkedin_url)->toBe('https://linkedin.com/in/johndoe')
        ->and($information->birthdate)->toBe('2000-01-01');
});
