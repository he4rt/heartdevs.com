<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Models;

use App\Concerns\HasAddress;
use Carbon\CarbonInterface;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use He4rt\Activity\Tracking\Concerns\HasInteractions;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\Database\Factories\UserFactory;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Concerns\HasProfileImages;
use He4rt\Identity\User\Enums\UserSituation;
use He4rt\Identity\User\Observers\UserObserver;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use He4rt\Profile\Models\WorkExperience;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property bool $is_donator
 * @property CarbonInterface|null $suspended_until
 * @property CarbonInterface|null $banned_at
 * @property CarbonInterface|null $first_login_at
 * @property CarbonInterface|null $deleted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property string|null $remember_token
 * @property-read UserSituation $situation
 * @property-read Collection<int, Role> $roles
 */
#[ObservedBy(classes: UserObserver::class)]
#[UseFactory(factoryClass: UserFactory::class)]
#[Table(name: 'users')]
#[Hidden('password', 'remember_token', 'email_verified_at')]
final class User extends Authenticatable implements FilamentUser, HasMedia, HasName
{
    use HasAddress;
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasInteractions;
    use HasProfileImages;
    use HasRoles;
    use HasUuids;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    public function isStaff(): bool
    {
        return $this->hasRole(UserRole::Staff);
    }

    public function isCompliance(): bool
    {
        return $this->hasRole(UserRole::Compliance);
    }

    /**
     * Quem gerencia usuários: edita identidade/perfil/endereço e soft-deleta.
     */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isStaff() || $this->isCompliance();
    }

    /**
     * Hard delete é exclusivo de Compliance (e super admin, que sobrepõe tudo).
     */
    public function canHardDeleteUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isCompliance();
    }

    /**
     * Recruiter/SquadCaptain veem a ficha do usuário, mas não a seção de Moderação.
     */
    public function canViewModeration(): bool
    {
        return $this->isSuperAdmin() || $this->isStaff() || $this->isCompliance();
    }

    /**
     * @return array<int, UserRole>
     */
    public function assignableRoles(): array
    {
        if ($this->isSuperAdmin()) {
            return UserRole::cases();
        }

        if (!$this->canManageUsers()) {
            return [];
        }

        return array_values(array_filter(
            UserRole::cases(),
            static fn (UserRole $role): bool => !in_array($role, [UserRole::SuperAdmin, UserRole::Compliance], strict: true),
        ));
    }

    /**
     * @return MorphMany<ExternalIdentity, $this>
     */
    public function providers(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'model');
    }

    /**
     * @return HasOne<Character, $this>
     */
    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @return HasManyThrough<ProfileSkill, Profile, $this>
     */
    public function profileSkills(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProfileSkill::class,
            Profile::class,
            'user_id',
            'profile_id',
            'id',
            'id',
        );
    }

    /**
     * @return HasManyThrough<WorkExperience, Profile, $this>
     */
    public function workExperiences(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkExperience::class,
            Profile::class,
            'user_id',
            'profile_id',
            'id',
            'id',
        );
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    public function registerMediaCollections(): void
    {
        $this->registerProfileImageCollections();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => app()->isProduction() ? $this->hasAnyRole([
                UserRole::SuperAdmin,
                UserRole::Staff,
                UserRole::Compliance,
                UserRole::Recruiter,
                UserRole::SquadCaptain,
            ]) : true,
            default => true
        };
    }

    public function getFilamentAvatarUrl(): string
    {

        return sprintf('https://github.com/%s.png', $this->username);
    }

    /**
     * @return Attribute<UserSituation, never>
     */
    protected function situation(): Attribute
    {
        return Attribute::get($this->resolveSituation(...));
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
            'first_login_at' => 'datetime',
        ];
    }

    private function resolveSituation(): UserSituation
    {
        $isBanned = $this->banned_at !== null;
        $isSuspended = $this->suspended_until !== null && $this->suspended_until->isFuture();

        return match (true) {
            $isBanned => UserSituation::Banned,
            $isSuspended => UserSituation::Suspended,
            default => UserSituation::Active,
        };
    }
}
