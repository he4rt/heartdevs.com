<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Ramsey\Uuid\Uuid;

class CreateAccountByExternalIdentity
{
    public function handle(string $tenantId, IdentityProvider $provider, string $providerId, string $username): ExternalIdentity
    {
        $existing = ExternalIdentity::query()
            ->where('provider', $provider->value)
            ->where('external_account_id', $providerId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $user = User::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'username' => $username,
            'name' => $username,
            'is_donator' => false,
        ]);

        $user->character()->create([
            'tenant_id' => $tenantId,
        ]);

        return ExternalIdentity::query()->create([
            'tenant_id' => $tenantId,
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'type' => $provider->getType(),
            'provider' => $provider->value,
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(),
            'external_account_id' => $providerId,
            'connected_at' => now(),
        ]);
    }
}
