<?php

declare(strict_types=1);

namespace App\Geo\Support;

use App\Geo\Models\GeoCountry;
use App\Geo\WorldApiClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class GeoLocation
{
    private const int CACHE_TTL = 60 * 60 * 24 * 30;

    /**
     * @return array<string, string> ISO3 => country name
     */
    public static function countries(): array
    {
        return GeoCountry::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(static fn (GeoCountry $country): array => [
                $country->iso3 => $country->name,
            ])
            ->all();
    }

    public static function countryLabel(?string $iso3): ?string
    {
        if (blank($iso3)) {
            return null;
        }

        /** @var string|null $name */
        $name = GeoCountry::query()
            ->where('iso3', mb_strtoupper($iso3))
            ->value('name');

        return $name;
    }

    /**
     * @return array<string, string> state name => state name
     */
    public static function statesFor(?string $iso3): array
    {
        return collect(self::statesPayload($iso3))
            ->sortBy('name')
            ->mapWithKeys(static fn (array $state): array => [
                (string) $state['name'] => (string) $state['name'],
            ])
            ->all();
    }

    /**
     * @return array<string, string> city name => city name
     */
    public static function citiesFor(?string $iso3, ?string $stateName, ?string $search = null): array
    {
        if (blank($stateName)) {
            return [];
        }

        /** @var array<string, mixed>|null $state */
        $state = collect(self::statesPayload($iso3))
            ->firstWhere('name', $stateName);

        $stateId = $state['id'] ?? null;

        if (!is_int($stateId)) {
            return [];
        }

        $term = mb_trim((string) $search);

        return collect(self::cityNames($stateId))
            ->when($term !== '', static fn (Collection $collection): Collection => $collection->filter(
                static fn (string $name): bool => self::matches($name, $term),
            ))
            ->sort()
            ->take(50)
            ->mapWithKeys(static fn (string $name): array => [$name => $name])
            ->all();
    }

    public static function formatLocation(?string $city, ?string $state, ?string $iso3): ?string
    {
        $country = filled($iso3) ? (self::countryLabel($iso3) ?? $iso3) : null;

        $location = collect([$city, $state, $country])
            ->filter()
            ->implode(', ');

        return $location !== '' ? $location : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function statesPayload(?string $iso3): array
    {
        if (blank($iso3)) {
            return [];
        }

        $iso3 = mb_strtoupper($iso3);

        /** @var list<array<string, mixed>> $payload */
        $payload = Cache::remember('geo.states.'.$iso3, self::CACHE_TTL, static function () use ($iso3): array {
            /** @var string|null $iso2 */
            $iso2 = GeoCountry::query()->where('iso3', $iso3)->value('iso2');

            if (blank($iso2)) {
                return [];
            }

            return resolve(WorldApiClient::class)->states((string) $iso2);
        });

        return $payload;
    }

    /**
     * @return list<string>
     */
    private static function cityNames(int $stateId): array
    {
        /** @var list<string> $names */
        $names = Cache::remember('geo.cities.'.$stateId, self::CACHE_TTL, static function () use ($stateId): array {
            $names = [];

            foreach (resolve(WorldApiClient::class)->cities($stateId) as $city) {
                if (is_string($city['name'] ?? null)) {
                    $names[] = $city['name'];
                }
            }

            return $names;
        });

        return $names;
    }

    private static function matches(string $value, string $term): bool
    {
        return str_contains(
            Str::lower(Str::ascii($value)),
            Str::lower(Str::ascii($term)),
        );
    }
}
