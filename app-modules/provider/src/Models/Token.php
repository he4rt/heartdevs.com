<?php

declare(strict_types=1);

namespace He4rt\Provider\Models;

use He4rt\Provider\Database\Factories\TokenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $provider_id
 * @property string $access_token
 * @property string $refresh_token
 */
final class Token extends Model
{
    use HasFactory;
    use HasUuids;
    protected $table = 'provider_tokens';

    protected $fillable = [
        'id',
        'provider_id',
        'access_token',
        'refresh_token',
        'expires_in',
    ];

    // TODO: expires token should have an human interface and auto refresh
    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    protected static function newFactory(): TokenFactory
    {
        return TokenFactory::new();
    }
}
