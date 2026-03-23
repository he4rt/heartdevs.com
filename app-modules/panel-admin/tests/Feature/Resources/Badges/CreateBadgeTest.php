<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\Badges\Pages\CreateBadge;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
    $this->tenant = Tenant::query()->first();
});

it('can render', function (): void {
    livewire(CreateBadge::class)->assertOk();
});

it('can create a badge', function (): void {
    livewire(CreateBadge::class)
        ->fillForm([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Test Badge',
            'description' => 'A test badge',
            'redeem_code' => 'TEST123',
            'provider' => IdentityProvider::Discord->value,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Badge::class, [
        'name' => 'Test Badge',
        'redeem_code' => 'TEST123',
    ]);
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateBadge::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'name is required' => ['name', '', 'required'],
    'redeem_code is required' => ['redeem_code', '', 'required'],
]);
