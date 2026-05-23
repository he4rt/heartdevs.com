<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $addressable_type
 * @property string $addressable_id
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $zip_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table(name: 'addresses')]
final class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
        'country',
        'state',
        'city',
        'zip_code',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
