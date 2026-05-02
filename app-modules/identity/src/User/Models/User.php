<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSubmission;
use He4rt\Events\Models\Pivot\EventAttend;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Database\Factories\UserFactory;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Concerns\InteractsWithTenants;
use He4rt\Identity\User\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property bool $is_donator
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[ObservedBy(UserObserver::class)]
final class User extends Authenticatable implements FilamentUser, HasMedia, HasName, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use InteractsWithTenants;
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'name',
        'email',
        'password',
        'is_donator',
        'suspended_until',
        'banned_at',
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
     * @return BelongsToMany<EventModel, $this, EventAttend, 'pivot'>
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
     * @return MorphMany<ExternalIdentity, $this>
     */
    public function providers(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'model');
    }

    /**
     * @return HasMany<EventSubmission, $this>
     */
    public function talks(): HasMany
    {
        return $this->hasMany(EventSubmission::class, 'user_id');
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
        return match ($panel->getId()) {
            'admin' => app()->isProduction() ? $this->isAdmin() : true,
            default => true
        };
    }

    public function getFilamentAvatarUrl(): string
    {

        return sprintf('https://github.com/%s.png', $this->username);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(function (): string {
            $name = str($this->name)
                ->explode(' ');

            $firstName = $name->shift();
            $lastName = $name->pop();

            /** @var string $result */
            $result = sprintf('%s %s', $firstName, $lastName);

            return $result;
        });
    }

    protected function casts(): array
    {
        return [
            'is_donator' => 'boolean',
            'password' => 'hashed',
            'suspended_until' => 'datetime',
            'banned_at' => 'datetime',
        ];
    }
}
