<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Loads the bundled GeoJSON of Brazilian states used to draw the choropleth in
 * the location dashboard. This is map *geometry* only — a static presentation
 * asset (IBGE, public data). Canonical state names and counts come from the geo
 * domain and the `addresses` table, never from here.
 *
 * Cached with `Cache::remember`, mirroring how the geo domain caches its
 * reference lookups (see `App\Geo\Models\GeoCountry`).
 */
final class BrazilStatesGeometry
{
    /**
     * The raw GeoJSON FeatureCollection handed to chartjs-chart-geo in the view.
     * Each feature carries `properties.name` (state name) and `properties.uf`.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return Cache::remember('panel-admin.geo.br-states', now()->addDays(30), static function (): array {
            $path = base_path('app-modules/panel-admin/resources/geo/br-states.geojson');

            /** @var array<string, mixed> $geojson */
            $geojson = json_decode(File::get($path), associative: true, flags: JSON_THROW_ON_ERROR);

            return $geojson;
        });
    }
}
