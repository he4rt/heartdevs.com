<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Sponsors\Filament\Resources\Sponsors\Pages\CreateSponsor;
use He4rt\Sponsors\Models\Sponsor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();

    livewire(CreateSponsor::class)
        ->assertOk();
});

it('should be able to register a sponsor', function (): void {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('image.jpg');
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();

    livewire(CreateSponsor::class)
        ->assertOk()
        ->fillForm([
            'tenant_id' => 1,
            'name' => 'sponsor name',
            'receipt' => $image,
            'homepage_url' => 'https://www.google.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Sponsor::class, 1);

    assertDatabaseHas(Sponsor::class, [
        'tenant_id' => 1,
        'name' => 'sponsor name',
        'homepage_url' => 'https://www.google.com',
    ]);

    $sponsor = Sponsor::query()->first();
    expect(count($sponsor->getMediaRepository()->all()))->toBe(1);
});
