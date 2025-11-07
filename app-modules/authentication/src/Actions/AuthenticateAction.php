<?php

declare(strict_types=1);

namespace He4rt\Authentication\Actions;

use Illuminate\Support\Facades\Date;
use He4rt\Authentication\DTO\OAuthUserDTO;
use He4rt\Authentication\Enums\OAuthProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AuthenticateAction
{
    public function withOAuth(string $tenantSlug, OAuthProviderEnum $oauthProvider, string $code): void
    {
        $tenant = $this->findTenantBySlug($tenantSlug);

        $clientProvider = $oauthProvider->getClient();
        $accessData = $clientProvider->auth($code);

        $user = $clientProvider->getAuthenticatedUser($accessData);

        $provider = Provider::query()
            ->where('provider', $user->provider)
            ->where('provider_id', $user->providerId)
            ->first();

        if (! $provider) {
            $provider = $this->registerNewUser($user, $tenant);
        }

        Auth::logout();
        Auth::login($provider->user);
        filament()->auth()->setUser($provider->user);
    }

    private function registerNewUser(OAuthUserDTO $userDTO, Tenant $tenant): Provider
    {
        $user = User::query()->firstOrCreate(['email' => $userDTO->email], [
            'id' => Uuid::uuid4()->toString(),
            'username' => $userDTO->username,
            'name' => $userDTO->name,
            'password' => Hash::make(Date::now()->getTimestamp().'-vai-brasil'),
            'is_donator' => false,
        ]);

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
