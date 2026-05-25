<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

final class FindOrCreateUserByProvider
{
    public function execute(OAuthUserDTO $oauthUser, Tenant $tenant): User
    {
        $user = $this->findExistingUser($oauthUser, $tenant);

        if (!$user instanceof User) {
            $user = User::query()->create([
                'username' => $oauthUser->username,
                'email' => $oauthUser->email,
                'name' => $oauthUser->name,
                'is_donator' => false,
            ]);
        }

        if (!$user->tenants()->where('tenants.id', $tenant->getKey())->exists()) {
            $user->tenants()->attach($tenant);
        }

        return $user;
    }

    private function findExistingUser(OAuthUserDTO $oauthUser, Tenant $tenant): ?User
    {
        $identity = ExternalIdentity::query()
            ->where('provider', $oauthUser->provider)
            ->where('external_account_id', $oauthUser->providerId)
            ->where('tenant_id', $tenant->getKey())
            ->first();

        if ($identity?->model instanceof User) {
            return $identity->model;
        }

        return User::query()
            ->where('email', $oauthUser->email)
            ->first();
    }
}
