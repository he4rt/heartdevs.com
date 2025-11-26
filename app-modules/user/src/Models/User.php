<?php

declare(strict_types=1);

namespace He4rt\User\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use He4rt\Character\Models\Character;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\Pivot\EventAttend;
use He4rt\Events\Models\Talk;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Concerns\InteractsWithTenants;
use He4rt\User\Database\Factories\UserFactory;
use He4rt\User\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $username
 * @property string $email
 * @property bool $is_donator
 */
#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable implements FilamentUser, HasMedia, HasName, HasTenants
{
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use InteractsWithTenants;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'name',
        'email',
        'password',
        'is_donator',
    ];

    public function isAdmin(): bool
    {
        return in_array($this->username, str(config('he4rt.admins'))->explode(',')->toArray(), true);
    }

    /**
     * @return HasOne<Address, $this>
     */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /**
     * @return BelongsToMany<EventModel, $this>
     */
    public function events(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                EventModel::class,
                'events_attendees',
                'user_id',
                'event_id'
            )
            ->using(EventAttend::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * @return HasOne<Information, $this>
     */
    public function information(): HasOne
    {
        return $this->hasOne(Information::class);
    }

    /**
     * @return HasMany<Provider, $this>
     */
    public function providers(): MorphMany
    {
        return $this->morphMany(Provider::class, 'model');
    }

    /**
     * @return HasMany<Talk, $this>
     */
    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class, 'user_id');
    }

    /**
     * @return HasOne<Character, $this>
     */
    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk('public');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): string
    {
        if ($this->providers->firstWhere('provider_name', 'github')) {
            return sprintf('https://github.com/%s.png', $this->username);
        }

        return sprintf('https://github.com/%s.png', $this->username);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_donator' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
