<?php

declare(strict_types=1);

use App\Models\Address;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Marketing\Pages\Location\Queries\MembersByState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'data' => [
                ['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil'],
            ],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'São Paulo'],
                ['id' => 2, 'name' => 'Minas Gerais'],
                ['id' => 3, 'name' => 'Bahia'],
            ],
        ]),
    ]);
});

test('it aggregates members by state, normalizing accents and casing', function (): void {
    $sp1 = User::factory()->create();
    $sp2 = User::factory()->create();
    $mg = User::factory()->create();

    Address::factory()->forUser($sp1)->create(['state' => 'São Paulo']);
    Address::factory()->forUser($sp2)->create(['state' => 'sao paulo']); // accent/case variation
    Address::factory()->forUser($mg)->create(['state' => 'Minas Gerais']);

    $data = new MembersByState()->get();

    expect($data['by_name']['sao paulo'])->toBe(2)
        ->and($data['by_name']['minas gerais'])->toBe(1)
        ->and($data['total'])->toBe(3)
        ->and($data['states_reached'])->toBe(2)
        ->and($data['states_total'])->toBe(3)
        ->and($data['top'][0]['name'])->toBe('São Paulo')
        ->and($data['top'][0]['members'])->toBe(2)
        ->and($data['top'][0]['share'])->toBe(66.7);
});

test('it ignores addresses whose state is not a recognized brazilian state', function (): void {
    $user = User::factory()->create();

    Address::factory()->forUser($user)->create(['state' => 'Nowhere']);

    $data = new MembersByState()->get();

    expect($data['total'])->toBe(0)
        ->and($data['by_name'])->toBeEmpty();
});
