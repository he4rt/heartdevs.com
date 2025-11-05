<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Badge\Filament\Resources\Badges\Pages\CreateBadge;
use He4rt\Badge\Models\Badge;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->createOne());
});

it('should render', function (): void {
    livewire(CreateBadge::class)
        ->assertOk();
});

it('should be able to create a badge', function (): void {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('image.jpg');
    $tenant = Tenant::factory()->create();

    livewire(CreateBadge::class)
        ->assertOk()
        ->fillForm([
            'tenant_id' => $tenant->getKey(),
            'provider' => ProviderEnum::Discord,
            'name' => 'name',
            'description' => 'description',
            'badge' => $image,
            'redeem_code' => 'redeem_code',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Badge::class, 1);
    assertDatabaseHas(Badge::class, [
        'tenant_id' => $tenant->getKey(),
        'provider' => ProviderEnum::Discord->value,
        'name' => 'name',
        'redeem_code' => 'redeem_code',
        'active' => 1,
    ]);
    $badge = Badge::query()->first();

    expect(count($badge->getMediaRepository()->all()))->toBe(1);
});
