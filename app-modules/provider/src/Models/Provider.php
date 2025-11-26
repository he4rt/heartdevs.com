<?php

declare(strict_types=1);

namespace He4rt\Provider\Models;

use He4rt\Message\Models\Message;
use He4rt\Provider\Database\Factories\ProviderFactory;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\User\Models\User;
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
 * @property Collection<Token> $tokens
 * @property string $user_id
 * @property int $tenant_id
 * @property string $provider_id
 * @property string $provider
 */
final class Provider extends Model
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
     * @return HasMany<Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    protected static function newFactory(): ProviderFactory
    {
        return ProviderFactory::new();
    }

    protected function casts(): array
    {
        return [
            'provider' => ProviderEnum::class,
        ];
    }

    protected function getMessagesCountAttribute(): int
    {
        return $this->messages()->count();
    }
}
