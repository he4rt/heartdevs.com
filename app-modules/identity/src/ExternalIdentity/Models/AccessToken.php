<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Models;

use He4rt\Identity\Database\Factories\AccessTokenFactory;
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
final class AccessToken extends Model
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

    /**
     * @return BelongsTo<ExternalIdentity, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ExternalIdentity::class, 'provider_id');
    }

    protected static function newFactory(): AccessTokenFactory
    {
        return AccessTokenFactory::new();
    }
}
