<?php

declare(strict_types=1);

use App\Geo\Support\GeoLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();

    Http::fake([
        'world.bmbc.cloud/api/countries*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 31, 'iso2' => 'BR', 'iso3' => 'BRA', 'name' => 'Brazil'],
                ['id' => 231, 'iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States'],
            ],
        ]),
        'world.bmbc.cloud/api/states*' => Http::response([
            'success' => true,
            'data' => [
                ['id' => 485, 'name' => 'Acre', 'cities' => [
                    ['id' => 1, 'name' => 'Rio Branco'],
                ]],
                ['id' => 486, 'name' => 'São Paulo', 'cities' => [
                    ['id' => 2, 'name' => 'São Paulo'],
                    ['id' => 3, 'name' => 'Campinas'],
                ]],
            ],
        ]),
    ]);
});

it('returns an ISO3 => name map of countries', function (): void {
    expect(GeoLocation::countries())->toBe([
        'BRA' => 'Brazil',
        'USA' => 'United States',
    ]);
});

it('resolves a country label from an ISO3 code', function (): void {
    expect(GeoLocation::countryLabel('BRA'))->toBe('Brazil')
        ->and(GeoLocation::countryLabel('USA'))->toBe('United States')
        ->and(GeoLocation::countryLabel(iso3: null))->toBeNull();
});

it('returns state names for a country', function (): void {
    expect(GeoLocation::statesFor('BRA'))->toBe([
        'Acre' => 'Acre',
        'São Paulo' => 'São Paulo',
    ]);
});

it('returns cities embedded in the states payload without a separate request', function (): void {
    expect(GeoLocation::citiesFor('BRA', 'São Paulo'))->toBe([
        'Campinas' => 'Campinas',
        'São Paulo' => 'São Paulo',
    ]);

    Http::assertNotSent(static fn ($request): bool => str_contains((string) $request->url(), '/cities'));
});

it('filters cities accent-insensitively', function (): void {
    expect(GeoLocation::citiesFor('BRA', 'São Paulo', 'sao'))
        ->toBe(['São Paulo' => 'São Paulo']);
});

it('caches the states payload for a country across calls', function (): void {
    GeoLocation::statesFor('BRA');
    GeoLocation::statesFor('BRA');

    Http::assertSentCount(2);

    $statesRequests = 0;

    Http::recorded(static function ($request) use (&$statesRequests): void {
        if (str_contains((string) $request->url(), '/states')) {
            $statesRequests++;
        }
    });

    expect($statesRequests)->toBe(1);
});

it('formats a full location string', function (): void {
    expect(GeoLocation::formatLocation('São Paulo', 'São Paulo', 'BRA'))
        ->toBe('São Paulo, São Paulo, Brazil');
});
