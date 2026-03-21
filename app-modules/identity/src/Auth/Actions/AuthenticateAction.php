<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AuthenticateAction
{
    public function withOAuth(OAuthStateDTO $state, IdentityProvider $oauthProvider, string $code): void
    {
        if ($state->tenant) {
            $this->authenticateTenant($state, $oauthProvider, $code);

            return;
        }

        // TODO: implement admin login only.
    }

    private function authenticateTenant(OAuthStateDTO $state, IdentityProvider $oauthProvider, string $code): void
    {
        $tenant = $this->findTenantBySlug($state->tenant);

        $clientProvider = $oauthProvider->getClient();
        $accessData = $clientProvider->auth($code);

        $user = $clientProvider->getAuthenticatedUser($accessData);

        $provider = ExternalIdentity::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('provider', $user->provider)
            ->where('external_account_id', $user->providerId)
            ->first();

        if (! $provider) {
            $provider = $this->registerNewUser($user, $tenant);
        }

        Auth::logout();
        Auth::login($provider->user);
        filament()->auth()->setUser($provider->user);
    }

    private function registerNewUser(OAuthUserDTO $userDTO, Tenant $tenant): ExternalIdentity
    {
        $user = auth()->check() ? auth()->user() : User::query()
            ->where('username', $userDTO->username)
            ->orWhere('email', $userDTO->email)
            ->first();

        if (! $user) {
            $user = User::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'username' => $userDTO->username,
                'email' => $userDTO->email,
                'name' => $userDTO->name,
                'password' => Hash::make(Date::now()->getTimestamp().'-vai-brasil'),
                'is_donator' => false,
            ]);
        }

        $user->tenants()->attach($tenant);

        /** @var ExternalIdentity $provider */
        $provider = $user->providers()->updateOrCreate([
            'tenant_id' => $tenant->getKey(),
            'provider' => IdentityProvider::from($userDTO->provider->value),
            'external_account_id' => $userDTO->providerId,
        ], [
            'type' => $userDTO->provider->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => $userDTO->credentials->toClientAccessManager(),
            'metadata' => [
                'email' => $userDTO->email,
                'avatar' => $userDTO->avatarUrl,
                'username' => $userDTO->username,
            ],
            'connected_at' => now(),
            'connected_by' => $user->id,
        ]);

        return $provider;
    }

    private function findTenantBySlug(string $tenantSlug): ?Tenant
    {
        return Tenant::query()->where('slug', $tenantSlug)->first();
    }
}
