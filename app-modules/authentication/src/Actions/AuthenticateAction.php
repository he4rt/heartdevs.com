<?php

declare(strict_types=1);

namespace He4rt\Authentication\Actions;

use He4rt\Authentication\DTO\OAuthStateDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;
use He4rt\Authentication\Enums\OAuthProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AuthenticateAction
{
    public function withOAuth(OAuthStateDTO $state, OAuthProviderEnum $oauthProvider, string $code): void
    {
        if ($state->tenant) {
            $this->authenticateTenant($state, $oauthProvider, $code);

            return;
        }

        // TODO: implement admin login only.
    }

    private function authenticateTenant(OAuthStateDTO $state, OAuthProviderEnum $oauthProvider, string $code): void
    {
        $tenant = $this->findTenantBySlug($state->tenant);

        $clientProvider = $oauthProvider->getClient();
        $accessData = $clientProvider->auth($code);

        $user = $clientProvider->getAuthenticatedUser($accessData);

        $provider = Provider::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('provider', $user->provider)
            ->where('provider_id', $user->providerId)
            ->first();

        if (! $provider) {
            $provider = $this->registerNewUser($user, $tenant);
        }

        if (! auth()->check()) {
            Auth::logout();
            Auth::login($provider->user);
            filament()->auth()->setUser($provider->user);
        }
    }

    private function registerNewUser(OAuthUserDTO $userDTO, Tenant $tenant): Provider
    {
        $user = auth()->check() ? auth()->user() : User::query()->firstOrCreate(['email' => $userDTO->email], [
            'id' => Uuid::uuid4()->toString(),
            'username' => $userDTO->username,
            'name' => $userDTO->name,
            'password' => Hash::make(Date::now()->getTimestamp().'-vai-brasil'),
            'is_donator' => false,
        ]);

        $user->tenants()->attach($tenant);

        /** @var Provider $provider */
        $provider = $user->providers()->updateOrCreate([
            'tenant_id' => $tenant->getKey(),
            'provider' => $userDTO->provider,
            'provider_id' => $userDTO->providerId,
        ], [
            'email' => $userDTO->email,
        ]);

        $provider->tokens()->create($userDTO->credentials->toDatabase());

        return $provider;
    }

    private function findTenantBySlug(string $tenantSlug): ?Tenant
    {
        return Tenant::query()->where('slug', $tenantSlug)->first();
    }
}
