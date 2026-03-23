<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Models;

use Carbon\Carbon;
use He4rt\Activity\Models\Message;
use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Models\PastSeason;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Database\Factories\TenantFactory;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property string $owner_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'owner_id',
        'active',
    ];

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
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users');
    }

    /**
     * @return MorphMany<ExternalIdentity, $this>
     */
    public function providers(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'model');
    }

    /**
     * @return HasMany<EventModel, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(EventModel::class);
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
