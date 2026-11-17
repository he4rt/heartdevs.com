<?php

declare(strict_types=1);

namespace He4rt\Identity\Users;

use App\Models\Concerns\CapturesActivityContext;
use He4rt\Identity\Database\Factories\Users\UserFactory;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Teams\Concerns\InteractsWithTenants;
use He4rt\Identity\Teams\Team;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read string $id
 * @property string $name
 * @property string $email
 * @property-read Carbon|null $email_verified_at
 * @property string|null $password
 * @property-read string|null $remember_token
 * @property string|null $locale
 * @property string|null $theme_color
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read Collection|Team[] $tenants
 * @property-read Collection|Role[] $roles
 * @property-read Collection|Permission[] $permissions
 * @property-read Collection<int, SocialAccount> $socialAccounts
 */
#[UsePolicy(class: UserPolicy::class)]
#[UseFactory(factoryClass: UserFactory::class)]
#[Table(name: 'identity_users')]
final class User extends Authenticatable implements HasMedia, MustVerifyEmail, PasskeyUser
{
    use CapturesActivityContext;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use InteractsWithMedia;
    use InteractsWithTenants;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** @return HasMany<SocialAccount, $this> */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * A person who signed up with a provider has no password until they set one.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function ownsSocialAccount(SocialProvider $provider, string $providerUserId): bool
    {
        return $this->socialAccounts()
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->exists();
    }

    public function hasSignInMethodBesides(SocialAccount $account): bool
    {
        $hasOtherSocialAccount = $this->socialAccounts()->whereKeyNot($account->id)->exists();

        return $this->hasPassword() || $this->passkeys()->exists() || $hasOtherSocialAccount;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
