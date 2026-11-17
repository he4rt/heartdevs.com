<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts;

use He4rt\Identity\Database\Factories\SocialAccounts\SocialAccountFactory;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A provider account (GitHub, Google…) that a user logs in with.
 *
 * @property-read string $id
 * @property string $user_id
 * @property SocialProvider $provider
 * @property string $provider_user_id
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read User $user
 */
#[UseFactory(factoryClass: SocialAccountFactory::class)]
#[Table(name: 'identity_social_accounts')]
final class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'provider' => SocialProvider::class,
        ];
    }
}
