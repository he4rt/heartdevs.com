<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class FindOrCreateUserByProvider
{
    public function __construct(
        private EnrichUserOnFirstLogin $enrichUser,
    ) {}

    public function execute(OAuthUserDTO $oauthUser, Tenant $tenant): User
    {
        $existing = $this->findExistingUser($oauthUser);

        $user = match ($existing instanceof User) {
            true => $this->enrichUser->execute($existing, $oauthUser),
            false => $this->createUser($oauthUser),
        };

        $alreadyBelongsToTenant = $user->tenants()->where('tenants.id', $tenant->getKey())->exists();

        if (!$alreadyBelongsToTenant) {
            $user->tenants()->attach($tenant);
        }

        return $user;
    }

    private function findExistingUser(OAuthUserDTO $oauthUser): ?User
    {
        $identity = ExternalIdentity::query()
            ->where('provider', $oauthUser->provider)
            ->where('external_account_id', $oauthUser->providerId)
            ->where('model_type', (new User)->getMorphClass())
            ->first();

        if ($identity?->model instanceof User) {
            return $identity->model;
        }

        if ($oauthUser->email !== null) {
            return User::query()
                ->where('email', $oauthUser->email)
                ->first();
        }

        return null;
    }

    private function createUser(OAuthUserDTO $oauthUser): User
    {
        $username = User::query()->where('username', $oauthUser->username)->exists()
            ? $this->generateSuffixedUsername($oauthUser->username)
            : $oauthUser->username;

        try {
            return DB::transaction(fn () => User::query()->create([
                'username' => $username,
                'email' => $oauthUser->email,
                'name' => $oauthUser->name,
                'is_donator' => false,
            ]));
        } catch (UniqueConstraintViolationException) {
            $username = $this->generateSuffixedUsername($oauthUser->username);

            return User::query()->create([
                'username' => $username,
                'email' => $oauthUser->email,
                'name' => $oauthUser->name,
                'is_donator' => false,
            ]);
        }
    }

    private function generateSuffixedUsername(string $base): string
    {
        $prefixLength = mb_strlen($base) + 1;

        $maxSuffix = User::query()
            ->where('username', 'LIKE', $base.'-%')
            ->pluck('username')
            ->reduce(static function (?int $max, string $username) use ($prefixLength): int {
                $suffix = (int) mb_substr($username, $prefixLength);

                return max($suffix, $max ?? 0);
            });

        return $base.'-'.($maxSuffix !== null ? $maxSuffix + 1 : 2);
    }
}
