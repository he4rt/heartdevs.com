<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Models;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Character\Models\PastSeason;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Database\Factories\TenantFactory;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string $owner_id
 * @property bool $active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return BelongsToMany<User, $this, TenantUser>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')->using(TenantUser::class);
    }

    /**
     * @return MorphMany<ExternalIdentity, $this>
     */
    public function providers(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'model');
    }

    /**
     * @return HasMany<Season, $this>
     */
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    /**
     * @return HasMany<PastSeason, $this>
     */
    public function pastSeasons(): HasMany
    {
        return $this->hasMany(PastSeason::class);
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
