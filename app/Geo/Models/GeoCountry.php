<?php

declare(strict_types=1);

namespace App\Geo\Models;

use App\Geo\WorldApiClient;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Sushi\Sushi;

/**
 * @property int $id
 * @property string $iso2
 * @property string|null $iso3
 * @property string $name
 */
#[WithoutIncrementing]
final class GeoCountry extends Model
{
    use Sushi;

    protected $keyType = 'int';

    /**
     * @var array<string, string>
     */
    protected $schema = [
        'id' => 'integer',
        'iso2' => 'string',
        'iso3' => 'string',
        'name' => 'string',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function getRows(): array
    {
        return Cache::remember(
            'geo.countries',
            60 * 60 * 24 * 30,
            static fn (): array => resolve(WorldApiClient::class)->countries(),
        );
    }
}
