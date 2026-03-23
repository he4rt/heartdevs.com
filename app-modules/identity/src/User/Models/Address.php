<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Models;

use He4rt\Identity\Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $state
 * @property string $city
 * @property string $zip_code
 * @property string $country
 */
final class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'user_address';

    protected $fillable = [
        'id',
        'user_id',
        'country',
        'state',
        'city',
        'zip_code',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
