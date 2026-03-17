<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Models;

use He4rt\Activity\Models\Message;
use He4rt\Identity\Database\Factories\ExternalIdentityFactory;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $id
 * @property User $user
 * @property Collection<AccessToken> $tokens
 * @property string $user_id
 * @property int $tenant_id
 * @property string $provider_id
 * @property string $provider
 */
final class ExternalIdentity extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'providers';

    protected $fillable = [
        'id',
        'tenant_id',
        'model_type',
        'model_id',
        'provider',
        'provider_id',
        'email',
        'avatar',
        'username',
    ];

    protected $appends = [
        'messages_count',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'model_id', 'id');
    }

    /**
     * @return HasMany<AccessToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(AccessToken::class, 'provider_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'provider_id');
    }

    protected static function newFactory(): ExternalIdentityFactory
    {
        return ExternalIdentityFactory::new();
    }

    protected function casts(): array
    {
        return [
            'provider' => IdentityProvider::class,
        ];
    }

    protected function getMessagesCountAttribute(): int
    {
        return $this->messages()->count();
    }
}
