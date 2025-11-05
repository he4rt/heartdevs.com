<?php

declare(strict_types=1);

namespace He4rt\User\Models;

use He4rt\Character\Models\Character;
use He4rt\Provider\Models\Provider;
use He4rt\User\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property string $id
 * @property string $username
 * @property bool $is_donator
 */
final class User extends Authenticatable
{
    use HasFactory;
    use HasUuids;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'username',
        'email',
        'password',
        'is_donator',
    ];

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function information(): HasOne
    {
        return $this->hasOne(Information::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class, 'model_id');
    }

    public function character(): HasOne
    {
        return $this->hasOne(Character::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_donator' => 'boolean',
        ];
    }
}
