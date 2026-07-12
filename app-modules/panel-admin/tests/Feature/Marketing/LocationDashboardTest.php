<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Marketing\Pages\Location\LocationDashboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'data' => [['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil']],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'data' => [['id' => 1, 'name' => 'São Paulo']],
        ]),
    ]);

    $admin = User::factory()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('the location dashboard renders for an admin', function (): void {
    livewire(LocationDashboard::class)
        ->assertSuccessful()
        ->assertSee(__('panel-admin::location.map.heading'));
});
